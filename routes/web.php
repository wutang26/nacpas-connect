<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\HotspotController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\WifiNetworkController;
use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\EmailConfigurationController;
use App\Http\Controllers\Admin\HardwareDashboardController;
use App\Http\Controllers\Admin\ProductController;
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
| User Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('users')
    ->name('users.')
    ->group(function () {

        Route::get('/', [UserController::class, 'index'])
            ->name('index');

        Route::get('/{user}', [UserController::class, 'show'])
            ->name('show');

        Route::post('/{user}/verify-email', [UserController::class, 'verifyEmail'])
    ->name('verify-email');

        Route::put('/{user}', [UserController::class, 'update'])
            ->middleware('permission:platform.users.manage')
            ->name('update');
    });
    

    /*
|--------------------------------------------------------------------------
| Role Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('roles')
    ->name('roles.')
    ->controller(RoleController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');

        Route::get('/create', 'create')->name('create');

        Route::post('/', 'store')->name('store');

        Route::get('/{role}/edit', 'edit')->name('edit');

        Route::put('/{role}', 'update')->name('update');

        Route::delete('/{role}', 'destroy')->name('destroy');
    });

    /*
|--------------------------------------------------------------------------
| Permission Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('permissions')
    ->name('permissions.')
    ->controller(PermissionController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

    
    /*
|--------------------------------------------------------------------------
| Platform Settings
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('settings')
    ->name('settings.')
    ->controller(PlatformSettingController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/', 'update')->name('update');
    });


    /*
|--------------------------------------------------------------------------
| Email Configuration
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('settings/email')
    ->name('settings.email.')
    ->controller(EmailConfigurationController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/', 'update')->name('update');
        Route::post('/test', 'test')->name('test');
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




/*
|--------------------------------------------------------------------------
| Hardware Management
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'permission:hardware.dashboard.view',
])
    ->prefix('hardware')
    ->name('hardware.')
    ->controller(HardwareDashboardController::class)
    ->group(function () {
        Route::get('/', 'index')->name('dashboard');
    });


/*
|--------------------------------------------------------------------------
| Hardware Products Catalogue
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('hardware/products')
    ->name('hardware.products.')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:hardware.products.view')
            ->name('index');

        Route::get('/create', 'create')
            ->middleware('permission:hardware.products.manage')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('permission:hardware.products.manage')
            ->name('store');

        Route::get('/{product}/edit', 'edit')
            ->middleware('permission:hardware.products.manage')
            ->name('edit');

        Route::put('/{product}', 'update')
            ->middleware('permission:hardware.products.manage')
            ->name('update');

        Route::delete('/{product}', 'destroy')
            ->middleware('permission:hardware.products.manage')
            ->name('destroy');
    });


require __DIR__.'/auth.php';
