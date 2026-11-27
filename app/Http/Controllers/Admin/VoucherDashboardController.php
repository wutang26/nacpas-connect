<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherAgent;
use App\Models\VoucherAgentPurchase;
use App\Models\VoucherBatch;
use App\Models\VoucherRedemption;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VoucherDashboardController extends Controller
{
    public function index(): Response
    {
        /*
         * This dashboard is protected by the super_admin role.
         * It intentionally displays platform-wide voucher information.
         */

        $voucherBySite = DB::table('vouchers')
            ->select('site_id')
            ->selectRaw('COUNT(*) AS total_vouchers')
            ->selectRaw(
                "SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) AS assigned"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) AS redeemed"
            )
            ->groupBy('site_id');

        $batchBySite = DB::table('voucher_batches')
            ->select('site_id')
            ->selectRaw('COUNT(*) AS total_batches')
            ->groupBy('site_id');

        $agentsBySite = DB::table('voucher_agents')
            ->select('site_id')
            ->selectRaw('COUNT(*) AS total_agents')
            ->selectRaw(
                "SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_agents"
            )
            ->groupBy('site_id');

        $purchasesBySite = DB::table('voucher_agent_purchases')
            ->select('site_id')
            ->selectRaw('COUNT(*) AS total_purchases')
            ->selectRaw(
                "SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_purchases"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_purchases"
            )
            ->groupBy('site_id');

        $redemptionsBySite = DB::table('voucher_redemptions')
            ->select('site_id')
            ->selectRaw('COUNT(*) AS total_redemptions')
            ->groupBy('site_id');

        $siteStatistics = DB::table('sites as s')
            ->leftJoinSub($voucherBySite, 'v', function ($join) {
                $join->on('v.site_id', '=', 's.id');
            })
            ->leftJoinSub($batchBySite, 'b', function ($join) {
                $join->on('b.site_id', '=', 's.id');
            })
            ->leftJoinSub($agentsBySite, 'a', function ($join) {
                $join->on('a.site_id', '=', 's.id');
            })
            ->leftJoinSub($purchasesBySite, 'p', function ($join) {
                $join->on('p.site_id', '=', 's.id');
            })
            ->leftJoinSub($redemptionsBySite, 'r', function ($join) {
                $join->on('r.site_id', '=', 's.id');
            })
            ->select('s.id', 's.name')
            ->selectRaw('COALESCE(b.total_batches, 0) AS total_batches')
            ->selectRaw('COALESCE(v.total_vouchers, 0) AS total_vouchers')
            ->selectRaw('COALESCE(v.available, 0) AS available')
            ->selectRaw('COALESCE(v.assigned, 0) AS assigned')
            ->selectRaw('COALESCE(v.redeemed, 0) AS redeemed')
            ->selectRaw('COALESCE(a.total_agents, 0) AS total_agents')
            ->selectRaw('COALESCE(a.active_agents, 0) AS active_agents')
            ->selectRaw('COALESCE(p.total_purchases, 0) AS total_purchases')
            ->selectRaw('COALESCE(p.paid_purchases, 0) AS paid_purchases')
            ->selectRaw('COALESCE(p.pending_purchases, 0) AS pending_purchases')
            ->selectRaw('COALESCE(r.total_redemptions, 0) AS total_redemptions')
            ->orderBy('s.name')
            ->get();

        /*
         * Revenue is grouped by currency to avoid adding different
         * currencies into one misleading total.
         */

        $wholesaleRevenue = VoucherAgentPurchase::query()
            ->where('status', 'paid')
            ->select('currency')
            ->selectRaw('SUM(total_amount) AS amount')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $redemptionRetailValue = VoucherRedemption::query()
            ->join(
                'vouchers',
                'vouchers.id',
                '=',
                'voucher_redemptions.voucher_id'
            )
            ->select('vouchers.currency')
            ->selectRaw('SUM(vouchers.retail_price) AS amount')
            ->groupBy('vouchers.currency')
            ->orderBy('vouchers.currency')
            ->get();

        $recentPurchases = VoucherAgentPurchase::query()
            ->with([
                'agent.user:id,name',
                'batch:id,batch_number',
                'site:id,name',
            ])
            ->latest()
            ->limit(8)
            ->get();

        $recentRedemptions = VoucherRedemption::query()
            ->with([
                'site:id,name',
                'package:id,name',
                'agent.user:id,name',
                'customer:id,name',
            ])
            ->latest('redeemed_at')
            ->limit(8)
            ->get();

        return Inertia::render('Vouchers/Dashboard', [
            'statistics' => [
                'total_batches' => VoucherBatch::count(),
                'active_batches' => VoucherBatch::where(
                    'status',
                    'active'
                )->count(),

                'total_vouchers' => Voucher::count(),
                'available_vouchers' => Voucher::where(
                    'status',
                    'available'
                )->count(),
                'assigned_vouchers' => Voucher::where(
                    'status',
                    'assigned'
                )->count(),
                'redeemed_vouchers' => Voucher::where(
                    'status',
                    'redeemed'
                )->count(),
                'void_vouchers' => Voucher::where(
                    'status',
                    'void'
                )->count(),

                'total_agents' => VoucherAgent::count(),
                'active_agents' => VoucherAgent::where(
                    'status',
                    'active'
                )->count(),
                'suspended_agents' => VoucherAgent::where(
                    'status',
                    'suspended'
                )->count(),

                'total_purchases' => VoucherAgentPurchase::count(),
                'pending_purchases' => VoucherAgentPurchase::where(
                    'status',
                    'pending'
                )->count(),
                'paid_purchases' => VoucherAgentPurchase::where(
                    'status',
                    'paid'
                )->count(),
                'cancelled_purchases' => VoucherAgentPurchase::where(
                    'status',
                    'cancelled'
                )->count(),

                'total_redemptions' => VoucherRedemption::count(),
            ],

            'wholesaleRevenue' => $wholesaleRevenue,
            'redemptionRetailValue' => $redemptionRetailValue,
            'siteStatistics' => $siteStatistics,
            'recentPurchases' => $recentPurchases,
            'recentRedemptions' => $recentRedemptions,
        ]);
    }
}
