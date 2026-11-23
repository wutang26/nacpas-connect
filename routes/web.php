<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\HotspotController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\WifiNetworkController;
use App\Http\Controllers\Admin\QrCodeController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::resource('packages', PackageController::class)
    ->except(['show'])
    ->middleware('verified');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('wifi-networks', WifiNetworkController::class)
    ->except(['show'])
    ->middleware('verified');
});

/*
|--------------------------------------------------------------------------
| QR Code Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('qr-codes')
    ->name('admin.qr-codes.')
    ->group(function () {

        Route::get('/', [QrCodeController::class, 'index'])
            ->middleware('permission:platform.qr_codes.view')
            ->name('index');

        Route::post('/sites/{site}/generate', [
            QrCodeController::class,
            'generate',
        ])
            ->middleware('permission:platform.qr_codes.manage')
            ->name('generate');

        Route::get('/{qrCode}/image', [
            QrCodeController::class,
            'image',
        ])
            ->middleware('permission:platform.qr_codes.view')
            ->name('image');

        Route::get('/{qrCode}/download', [
            QrCodeController::class,
            'download',
        ])
            ->middleware('permission:platform.qr_codes.view')
            ->name('download');
    });


/*
|--------------------------------------------------------------------------
| Customer Captive Portal
|--------------------------------------------------------------------------
*/

Route::prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/{site:slug}', [HotspotController::class, 'show'])
            ->name('show');
    });



/*
|--------------------------------------------------------------------------
| Customer Captive Portal
|--------------------------------------------------------------------------
*/

Route::prefix('portal')
    ->name('portal.')
    ->group(function () {

        Route::get('/{site:slug}', [HotspotController::class, 'show'])
            ->name('show');

        Route::post('/{site:slug}/checkout', [
            \App\Http\Controllers\Portal\PaymentController::class,
            'checkout'
        ])
            ->middleware('throttle:10,1')
            ->name('checkout');

        Route::get('/{site:slug}/payment/{reference}', [
            \App\Http\Controllers\Portal\PaymentController::class,
            'pending'
        ])
            ->name('payment.pending');
    });



require __DIR__.'/auth.php';
