<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\Vouchers\VoucherRedemptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function redeem(
        Request $request,
        Site $site,
        VoucherRedemptionService $redemptionService
    ): RedirectResponse {
        abort_if(
            $site->status !== 'active' ||
            $site->organization?->status !== 'active',
            404
        );

        $validated = $request->validate([
            'voucher_code' => [
                'required',
                'string',
                'max:40',
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
            ],
            'context_token' => [
                'required',
                'uuid',
            ],
        ]);

        $result = $redemptionService->redeem(
            $site,
            $validated['voucher_code'],
            $validated['phone'],
            $validated['context_token'],
            $request->session()->getId()
        );

        /*
         * Keep the original token server-side, never in the URL
         * or Inertia response.
         */
        $request->session()->put(
            "subscription_access_tokens.{$site->id}.voucher.{$result['subscription']->reference}",
            $result['access_token']
        );
        $message = match ($result['command_status']) {
            'pending', 'processing' =>
                'Voucher imekubaliwa. Ombi la kuidhinisha kifaa limetumwa kwenye mfumo wa mtandao.',
            'succeeded' =>
                'Voucher imekubaliwa na ombi la kuidhinisha kifaa limekamilika.',
            'unknown' =>
                'Voucher imekubaliwa, lakini hali ya kuidhinisha kifaa haijathibitishwa. Tafadhali subiri au wasiliana na msimamizi.',
            'not_configured' =>
                'Voucher imekubaliwa. Muunganisho wa mtandao wa eneo hili haujawa tayari.',
            default =>
                'Voucher imekubaliwa, lakini ombi la kuidhinisha kifaa halikuweza kutumwa. Tafadhali wasiliana na msimamizi.',
        };

        return back()->with('success', $message);
    }
}
