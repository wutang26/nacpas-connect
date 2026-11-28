<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Voucher;
use App\Models\VoucherAgent;
use App\Models\VoucherBatch;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;

class VoucherPrintController extends Controller
{
    /**
     * Admin printing: only vouchers that are still unassigned inventory.
     */
    public function batch(Request $request, VoucherBatch $batch): View
    {
        $this->manageableSites($request)
            ->whereKey($batch->site_id)
            ->firstOrFail();

        abort_if(
            $batch->status !== 'active',
            422,
            'Batch iliyofungwa haiwezi kuchapishwa.'
        );

        $validated = $request->validate([
            'width' => ['nullable', 'in:58,80'],
            'ids' => ['sometimes', 'array', 'min:1', 'max:5000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $query = Voucher::query()
            ->where('voucher_batch_id', $batch->id)
            ->where('site_id', $batch->site_id)
            ->where('status', 'available')
            ->whereNull('voucher_agent_id')
            ->whereNull('voucher_agent_purchase_id')
            ->whereNotNull('encrypted_code')
            ->with([
                'batch:id,batch_number',
                'package:id,name',
            ])
            ->orderBy('id');

        if (isset($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
        }

        $records = $query->get([
            'id',
            'voucher_batch_id',
            'package_id',
            'encrypted_code',
            'retail_price',
            'currency',
            'expires_at',
        ]);

        if (
            isset($validated['ids']) &&
            $records->count() !== count($validated['ids'])
        ) {
            abort(404, 'Baadhi ya voucher ulizochagua hazipatikani tena kwenye inventory.');
        }

        abort_if($records->isEmpty(), 404, 'Hakuna voucher zinazoweza kuchapishwa.');

        $batch->load([
            'site:id,name,slug',
            'package:id,name,duration_minutes',
        ]);

        return view('vouchers.print', [
            'heading' => 'NACPASI Voucher',
            'siteName' => $batch->site->name,
            'packageName' => $batch->package?->name ?? 'WiFi Access',
            'batchNumber' => $batch->batch_number,
            'paperWidth' => (int) ($validated['width'] ?? 58),
            'vouchers' => $this->prepareVouchers(
                $records,
                url('/portal/' . rawurlencode($batch->site->slug))
            ),
        ]);
    }

    /**
     * Agent printing: only this agent's assigned, paid and unused vouchers.
     */
    public function agent(Request $request): View
    {
        $agent = VoucherAgent::query()
            ->with('site:id,name,slug')
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_if(
            $agent->status !== 'active',
            403,
            'Akaunti yako ya uwakala haipo active.'
        );

        $validated = $request->validate([
            'width' => ['nullable', 'in:58,80'],
            'ids' => ['sometimes', 'array', 'min:1', 'max:5000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $query = Voucher::query()
            ->where('voucher_agent_id', $agent->id)
            ->where('site_id', $agent->site_id)
            ->where('status', 'assigned')
            ->whereNotNull('encrypted_code')
            ->whereHas('agentPurchase', fn ($q) =>
                $q->where('status', 'paid')
            )
            ->with([
                'batch:id,batch_number',
                'package:id,name',
            ])
            ->orderBy('id');

        if (isset($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
        }

        $records = $query->get([
            'id',
            'voucher_batch_id',
            'package_id',
            'encrypted_code',
            'retail_price',
            'currency',
            'expires_at',
        ]);

        if (
            isset($validated['ids']) &&
            $records->count() !== count($validated['ids'])
        ) {
            abort(404, 'Baadhi ya voucher ulizochagua si zako au hazijapatikana.');
        }

        abort_if($records->isEmpty(), 404, 'Hakuna voucher ambazo hazijatumika za kuchapisha.');

        return view('vouchers.print', [
            'heading' => 'NACPASI Agent Vouchers',
            'siteName' => $agent->site->name,
            'packageName' => null,
            'batchNumber' => null,
            'paperWidth' => (int) ($validated['width'] ?? 58),
            'vouchers' => $this->prepareVouchers(
                $records,
                url('/portal/' . rawurlencode($agent->site->slug))
            ),
        ]);
    }

    /**
     * Limit admin access to sites the authenticated user manages.
     */
    private function manageableSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query();
        }

        $organizationIds = $user->ownedOrganizations()->pluck('id');

        $memberOrganizationIds = $user->organizations()
            ->wherePivot('status', 'active')
            ->wherePivotIn('role', ['owner', 'admin', 'manager'])
            ->pluck('organizations.id');

        $organizationIds = $organizationIds
            ->merge($memberOrganizationIds)
            ->unique()
            ->values();

        $siteIds = $user->sites()
            ->wherePivot('status', 'active')
            ->wherePivotIn('role', ['owner', 'manager'])
            ->pluck('sites.id');

        return Site::query()->where(function ($query) use (
            $organizationIds,
            $siteIds
        ) {
            $query->whereIn('organization_id', $organizationIds)
                ->orWhereIn('id', $siteIds);
        });
    }

    /**
     * Decrypt only after authorization and create QR images for printing.
     */
    private function prepareVouchers($records, string $portalUrl): array
    {
        $writer = new SvgWriter();

        return $records->map(function (Voucher $voucher) use ($writer, $portalUrl) {
            $code = Crypt::decryptString($voucher->encrypted_code);

            $qr = new EndroidQrCode(
                data: $portalUrl . '#voucher=' . rawurlencode($code),
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 180,
                margin: 5,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255),
            );

            $svg = $writer->write($qr)->getString();

            return [
                'id' => $voucher->id,
                'code' => $code,
                'qr' => 'data:image/svg+xml;base64,' . base64_encode($svg),
                'price' => $voucher->retail_price,
                'currency' => $voucher->currency,
                'expires_at' => $voucher->expires_at,
                'batch_number' => $voucher->batch?->batch_number,
                'package_name' => $voucher->package?->name,
            ];
        })->all();
    }
}
