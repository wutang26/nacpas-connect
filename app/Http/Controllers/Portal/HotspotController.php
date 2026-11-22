<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Inertia\Inertia;
use Inertia\Response;

class HotspotController extends Controller
{
    public function show(Site $site): Response
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

        return Inertia::render('Portal/Index', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'region' => $site->region,
                'district' => $site->district,
            ],

            'packages' => $packages,
        ]);
    }
}