<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Voucher;
use App\Models\VoucherAgent;
use App\Models\VoucherAgentPurchase;
use App\Models\VoucherRedemption;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherReportController extends Controller
{
    private function manageableSites(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return Site::query();
        }

        $organizationIds = $user->ownedOrganizations()->pluck('id')
            ->merge(
                $user->organizations()
                    ->wherePivot('status', 'active')
                    ->wherePivotIn('role', ['owner', 'admin', 'manager'])
                    ->pluck('organizations.id')
            )->unique();

        $siteIds = $user->sites()
            ->wherePivot('status', 'active')
            ->wherePivotIn('role', ['owner', 'manager'])
            ->pluck('sites.id');

        return Site::query()->where(function ($q) use ($organizationIds, $siteIds) {
            $q->whereIn('organization_id', $organizationIds)
                ->orWhereIn('id', $siteIds);
        });
    }

    public function index(Request $request): Response
    {
        $siteIds = $this->manageableSites($request)->select('id');

        $redemptions = VoucherRedemption::query()
            ->with([
                'site:id,name',
                'agent.user:id,name',
                'package:id,name',
                'customer:id,name,phone',
            ])
            ->whereIn('site_id', $siteIds)
            ->latest('redeemed_at')
            ->paginate(20)
            ->withQueryString();

        $purchases = VoucherAgentPurchase::query()
            ->whereIn('site_id', $siteIds);

        $vouchers = Voucher::query()
            ->whereIn('site_id', $siteIds);

        return Inertia::render('Vouchers/Reports/Index', [
            'redemptions' => $redemptions,
            'statistics' => [
                'total_vouchers' => (clone $vouchers)->count(),
                'available' => (clone $vouchers)->where('status', 'available')->count(),
                'assigned' => (clone $vouchers)->where('status', 'assigned')->count(),
                'redeemed' => (clone $vouchers)->where('status', 'redeemed')->count(),
                'paid_purchases' => (clone $purchases)->where('status', 'paid')->count(),
                'wholesale_revenue' => (clone $purchases)
                    ->where('status', 'paid')->sum('total_amount'),
                'redemption_retail_value' => VoucherRedemption::query()
                    ->whereIn('voucher_redemptions.site_id', $siteIds)
                    ->join('vouchers', 'vouchers.id', '=', 'voucher_redemptions.voucher_id')
                    ->sum('vouchers.retail_price'),
                'agents' => VoucherAgent::query()
                    ->whereIn('site_id', $siteIds)
                    ->where('status', 'active')
                    ->count(),
            ],
        ]);
    }
}
