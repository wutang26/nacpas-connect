<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\VoucherAgent;
use App\Models\VoucherBatch;
use App\Models\VoucherAgentPurchase;
use App\Services\Vouchers\VoucherAgentPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VoucherAgentDashboardController extends Controller
{
        private function agent(Request $request): VoucherAgent
        {
            $agent = VoucherAgent::query()
                ->with(['site:id,name,code'])
                ->where('user_id', $request->user()->id)
                ->first();

            abort_if(
                !$agent,
                403,
                'Akaunti yako haijasajiliwa kama wakala wa NACPASI.'
            );

            abort_if(
                $agent->status !== 'active',
                403,
                'Akaunti yako ya uwakala imesimamishwa. Wasiliana na msimamizi.'
            );

            return $agent;
        }

    public function index(Request $request): Response
    {
        $agent = $this->agent($request);

        $purchases = $agent->purchases()
            ->with('batch:id,batch_number')
            ->latest()
            ->paginate(10);

        // Only this authenticated agent's paid inventory is decrypted.
        $vouchers = $agent->vouchers()
            ->whereHas('agentPurchase', fn ($q) =>
                $q->where('status', 'paid')
            )
            ->whereIn('status', ['assigned', 'redeemed'])
            ->whereNotNull('encrypted_code')
            ->with('batch:id,batch_number')
            ->latest()
            ->get()
            ->map(function ($voucher) {
                return [
                    'id' => $voucher->id,
                    'code' => Crypt::decryptString($voucher->encrypted_code),
                    'code_suffix' => $voucher->code_suffix,
                    'status' => $voucher->status,
                    'retail_price' => $voucher->retail_price,
                    'currency' => $voucher->currency,
                    'batch_number' => $voucher->batch?->batch_number,
                    'assigned_at' => $voucher->assigned_at,
                    'redeemed_at' => $voucher->redeemed_at,
                    'expires_at' => $voucher->expires_at,
                ];
            });

        return Inertia::render('VoucherAgent/Dashboard', [
            'agent' => $agent,
            'purchases' => $purchases,
            'vouchers' => $vouchers,
            'statistics' => [
                'purchases' => $agent->purchases()->count(),
                'pending' => $agent->purchases()->where('status', 'pending')->count(),
                'assigned' => $agent->vouchers()->where('status', 'assigned')->count(),
                'redeemed' => $agent->redemptions()->count(),
                'potentialRetailValue' => $agent->vouchers()
                    ->where('status', 'assigned')
                    ->sum('retail_price'),
            ],
        ]);
    }

    public function availableBatches(Request $request): Response
    {
        $agent = $this->agent($request);

        abort_if($agent->status !== 'active', 403, 'Agent account imesimamishwa.');

        $batches = VoucherBatch::query()
            ->where('site_id', $agent->site_id)
            ->where('status', 'active')
            ->where(fn ($q) =>
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now())
            )
            ->with('package:id,name')
            ->withCount([
                'vouchers as available_stock' => fn ($q) =>
                    $q->where('status', 'available')
                        ->whereNull('voucher_agent_id')
                        ->whereNull('voucher_agent_purchase_id'),
            ])
            ->orderByDesc('created_at')
            ->get([
                'id',
                'batch_number',
                'site_id',
                'package_id',
                'wholesale_unit_price',
                'retail_unit_price',
                'currency',
                'expires_at',
            ]);

        return Inertia::render('VoucherAgent/Batches', [
            'agent' => $agent,
            'batches' => $batches,
        ]);
    }

    public function requestPurchase(
        Request $request,
        VoucherAgentPurchaseService $service
    ): RedirectResponse {
        $agent = $this->agent($request);

        abort_if($agent->status !== 'active', 403, 'Agent account imesimamishwa.');

        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:voucher_batches,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        $batch = VoucherBatch::query()
            ->whereKey($validated['batch_id'])
            ->where('site_id', $agent->site_id)
            ->firstOrFail();

        $purchase = $service->requestPurchase(
            $agent,
            $batch,
            (int) $validated['quantity']
        );

        return redirect()
            ->route('voucher-agent.dashboard')
            ->with('success', 'Ombi lako limepokelewa. Subiri malipo yathibitishwe.')
            ->with('purchase_reference', $purchase->reference);
    }
}
