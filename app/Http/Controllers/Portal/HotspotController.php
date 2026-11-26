<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\WifiPortalContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HotspotController extends Controller
{
    public function show(Request $request, Site $site): Response
    {
        $site->load('organization');

        abort_if(
            $site->status !== 'active' ||
            $site->organization?->status !== 'active',
            404
        );

        $packages = $site->packages()
            ->where('is_active', true)
            ->orderBy('price')
            ->get([
                'id',
                'name',
                'description',
                'price',
                'currency',
                'duration_minutes',
                'download_speed_kbps',
                'upload_speed_kbps',
                'data_limit_bytes',
                'device_limit',
            ]);

        $contextToken = $this->captureOmadaContext(
            $request,
            $site
        );

        return Inertia::render('Portal/Index', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'region' => $site->region,
                'district' => $site->district,
            ],
            'packages' => $packages,
            'contextToken' => $contextToken,
        ]);
    }

    /**
     * Capture and securely bind Omada External Portal parameters
     * to the current Laravel browser session.
     */
    private function captureOmadaContext(
        Request $request,
        Site $site
    ): ?string {
        $parameterNames = [
            'clientMac',
            'clientIp',
            'apMac',
            'ssidName',
            'radioId',
            'site',
            'redirectUrl',
            't',
        ];

        $hasOmadaParameters = collect($parameterNames)
            ->contains(
                fn ($name) => $request->query->has($name)
            );

        // Normal portal visits do not require Omada parameters.
        if (!$hasOmadaParameters) {
            return null;
        }

        $validator = Validator::make(
            $request->query(),
            [
                'clientMac' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/\A(?:[0-9a-fA-F]{2}[:-]){5}[0-9a-fA-F]{2}\z/',
                ],

                'clientIp' => [
                    'required',
                    'ip',
                    'max:45',
                ],

                'apMac' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/\A(?:[0-9a-fA-F]{2}[:-]){5}[0-9a-fA-F]{2}\z/',
                ],

                'ssidName' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'radioId' => [
                    'required',
                    'string',
                    'max:50',
                ],

                'site' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'redirectUrl' => [
                    'required',
                    'string',
                    'url:http,https',
                    'max:2048',
                ],

                't' => [
                    'required',
                    'integer',
                    'min:1',
                ],
            ]
        );

        if ($validator->fails()) {
            abort(
                422,
                'Taarifa zilizotumwa na Omada hazijakamilika au si sahihi.'
            );
        }

        $data = $validator->validated();

        /*
         * Validate redirect URL structure.
         *
         * HTTP is accepted because Omada may send HTTP connectivity
         * check URLs. This URL is stored as context only. It must
         * never be used as proof of payment or WiFi authorization.
         */
        $redirectUrl = parse_url($data['redirectUrl']);

        if (
            !is_array($redirectUrl) ||
            !in_array(
                strtolower($redirectUrl['scheme'] ?? ''),
                ['http', 'https'],
                true
            ) ||
            empty($redirectUrl['host']) ||
            isset($redirectUrl['user']) ||
            isset($redirectUrl['pass']) ||
            preg_match('/[\r\n]/', $data['redirectUrl'])
        ) {
            abort(
                422,
                'Omada imetuma redirect URL isiyoruhusiwa.'
            );
        }

        /*
         * Reject stale Omada requests.
         *
         * Allow 10 minutes of clock difference to accommodate
         * network delays and small clock synchronization differences.
         */
        $omadaTimestamp = (int) $data['t'];

        abort_if(
            abs(now()->timestamp - $omadaTimestamp) > 600,
            422,
            'Omada request imepitwa na muda. Tafadhali reconnect WiFi.'
        );

        /*
         * Bind the captured context to the current Laravel session.
         */
        $sessionId = $request->session()->getId();

        abort_if(
            !$sessionId,
            419,
            'Browser session haijaanzishwa. Tafadhali jaribu tena.'
        );

        $contextToken = (string) Str::uuid();

        WifiPortalContext::create([
            'context_token' => $contextToken,

            'session_hash' => hash(
                'sha256',
                $sessionId
            ),

            'site_id' => $site->id,

            'client_mac' => strtoupper(
                str_replace('-', ':', $data['clientMac'])
            ),

            'client_ip' => $data['clientIp'],

            'ap_mac' => strtoupper(
                str_replace('-', ':', $data['apMac'])
            ),

            'ssid_name' => $data['ssidName'],

            'radio_id' => $data['radioId'],

            'controller_site_id' => $data['site'],

            'redirect_url' => $data['redirectUrl'],

            'omada_timestamp' => $omadaTimestamp,

            'context_status' => 'captured',

            'authorization_status' => 'not_started',

            'expires_at' => now()->addMinutes(60),
        ]);

        return $contextToken;
    }
}
