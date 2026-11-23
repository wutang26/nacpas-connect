<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrCode as SiteQrCode;
use App\Models\Site;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class QrCodeController extends Controller
{
    /**
     * Display QR codes for all sites.
     */
    public function index(): InertiaResponse
    {
        $sites = Site::query()
            ->with([
                'qrCodes' => function ($query) {
                    $query->where('qr_type', 'portal');
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Site $site) {
                $qrCode = $site->qrCodes->first();

                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'slug' => $site->slug,
                    'code' => $site->code,
                    'status' => $site->status,

                    'portal_url' => route('portal.show', [
                        'site' => $site->slug,
                    ]),

                    'qr_code' => $qrCode ? [
                        'id' => $qrCode->id,
                        'label' => $qrCode->label,
                        'code' => $qrCode->code,
                        'target_url' => $qrCode->target_url,
                        'status' => $qrCode->status,
                        'image_url' => route(
                            'admin.qr-codes.image',
                            $qrCode->id
                        ),
                        'download_url' => route(
                            'admin.qr-codes.download',
                            $qrCode->id
                        ),
                    ] : null,
                ];
            });

        return Inertia::render('QrCodes/Index', [
            'sites' => $sites,
            'statistics' => [
                'total_sites' => $sites->count(),
                'generated' => $sites->filter(
                    fn ($site) => $site['qr_code'] !== null
                )->count(),
                'not_generated' => $sites->filter(
                    fn ($site) => $site['qr_code'] === null
                )->count(),
            ],
        ]);
    }

    /**
     * Generate or update the portal QR code for a site.
     */
    public function generate(Site $site)
    {
        DB::transaction(function () use ($site) {
            // Lock the Site row to prevent simultaneous duplicate generation.
            $lockedSite = Site::query()
                ->lockForUpdate()
                ->findOrFail($site->id);

            $qrCode = $lockedSite->qrCodes()
                ->where('qr_type', 'portal')
                ->first();

            if (!$qrCode) {
                $qrCode = new SiteQrCode();

                $qrCode->site()->associate($lockedSite);
                $qrCode->qr_type = 'portal';
                $qrCode->code = (string) Str::uuid();
            }

            $qrCode->label = 'Portal - ' . $lockedSite->name;

            $qrCode->target_url = route('portal.show', [
                'site' => $lockedSite->slug,
            ]);

            $qrCode->status = 'active';
            $qrCode->expires_at = null;

            $qrCode->save();
        });

        return back()->with(
            'success',
            'Portal QR Code generated successfully.'
        );
    }

    /**
     * Display QR code as SVG.
     */
    public function image(SiteQrCode $qrCode): Response
    {
        abort_unless(
            $qrCode->qr_type === 'portal'
            && $qrCode->status === 'active',
            404
        );

        $result = $this->createSvg($qrCode);

        return response($result, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Download QR code as SVG.
     */
    public function download(SiteQrCode $qrCode): Response
    {
        abort_unless(
            $qrCode->qr_type === 'portal'
            && $qrCode->status === 'active',
            404
        );

        $result = $this->createSvg($qrCode);

        $siteName = $qrCode->site?->name ?? 'site';

        $filename = Str::slug($siteName) . '-portal-qr.svg';

        return response($result, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Create SVG QR code content.
     */
    private function createSvg(SiteQrCode $record): string
    {
        $qr = new EndroidQrCode(
            data: $record->target_url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 420,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new SvgWriter();

        $result = $writer->write($qr);

        return $result->getString();
    }
}
