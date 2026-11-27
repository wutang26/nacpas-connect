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
use App\Http\Controllers\Admin\DeviceAssetController;
use App\Http\Controllers\Admin\NetworkConnectionController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\SaasPlanController;
use App\Http\Controllers\Admin\OrganizationSubscriptionController;
use App\Http\Controllers\Admin\SaasPaymentController;
use App\Http\Controllers\Admin\CaptivePortalSessionController;
use App\Http\Controllers\Admin\VoucherBatchController;
use App\Http\Controllers\VoucherPrintController;
use App\Http\Controllers\Admin\VoucherAgentController;
use App\Http\Controllers\Admin\VoucherAgentPurchaseController;
use App\Http\Controllers\Admin\VoucherReportController;
use App\Http\Controllers\Admin\VoucherDashboardController;
use App\Http\Controllers\Agent\VoucherAgentDashboardController;
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

        Route::post('/{site:slug}/redeem-voucher', [
    \App\Http\Controllers\Portal\VoucherController::class,
    'redeem',
])
    ->middleware('throttle:10,1')
    ->name('voucher.redeem');
    
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
| Hardware Device Asset Tracking
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('hardware/assets')
    ->name('hardware.assets.')
    ->controller(DeviceAssetController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:hardware.inventory.view')
            ->name('index');

        Route::get('/create', 'create')
            ->middleware('permission:hardware.inventory.manage')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('permission:hardware.inventory.manage')
            ->name('store');
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


    /*
|--------------------------------------------------------------------------
| Network Connection Monitor
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
])
    ->prefix('network-connections')
    ->name('network-connections.')
    ->controller(NetworkConnectionController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->middleware('permission:network.management.view')
            ->name('index');

        Route::post('/{integration}/test', 'test')
            ->middleware([
                'permission:network.management.view',
                'throttle:5,1',
            ])
            ->name('test');

        Route::put('/{integration}/settings', 'updateOmadaSettings')
            ->middleware([
                'permission:network.credentials.manage',
                'throttle:5,1',
            ])
            ->name('settings.update');
    });

/*
|--------------------------------------------------------------------------
| Organization Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('organizations')
    ->name('organizations.')
    ->controller(OrganizationController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:platform.organizations.view')
            ->name('index');

        Route::get('/create', 'create')
            ->middleware('permission:platform.organizations.create')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('permission:platform.organizations.create')
            ->name('store');

        Route::get('/{organization}/edit', 'edit')
            ->middleware('permission:platform.organizations.update')
            ->name('edit');

        Route::get('/{organization}', 'show')
            ->middleware('permission:platform.organizations.view')
            ->name('show');

        Route::put('/{organization}', 'update')
            ->middleware('permission:platform.organizations.update')
            ->name('update');
    });


/*
|--------------------------------------------------------------------------
| Site Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('sites')
    ->name('sites.')
    ->controller(SiteController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:platform.sites.view')
            ->name('index');

        Route::get('/create', 'create')
            ->middleware('permission:platform.sites.create')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('permission:platform.sites.create')
            ->name('store');

        Route::get('/{site}/edit', 'edit')
            ->middleware('permission:platform.sites.update')
            ->name('edit');

        Route::get('/{site}', 'show')
            ->middleware('permission:platform.sites.view')
            ->name('show');

        Route::put('/{site}', 'update')
            ->middleware('permission:platform.sites.update')
            ->name('update');
    });


/*
|--------------------------------------------------------------------------
| SaaS Plans Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('saas-plans')
    ->name('saas-plans.')
    ->controller(SaasPlanController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:platform.saas_plans.view')
            ->name('index');

        Route::get('/create', 'create')
            ->middleware('permission:platform.saas_plans.manage')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('permission:platform.saas_plans.manage')
            ->name('store');

        Route::get('/{saasPlan}/edit', 'edit')
            ->middleware('permission:platform.saas_plans.manage')
            ->name('edit');

        Route::get('/{saasPlan}', 'show')
            ->middleware('permission:platform.saas_plans.view')
            ->name('show');

        Route::put('/{saasPlan}', 'update')
            ->middleware('permission:platform.saas_plans.manage')
            ->name('update');
    });


    //Saas Plan SUbsrcriptions
    
/*
|--------------------------------------------------------------------------
| SaaS Subscriptions Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('subscriptions')
    ->name('subscriptions.')
    ->controller(OrganizationSubscriptionController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:platform.subscriptions.view')
            ->name('index');

        Route::get('/create', 'create')
            ->middleware('permission:platform.subscriptions.manage')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('permission:platform.subscriptions.manage')
            ->name('store');

        Route::get('/{subscription}/edit', 'edit')
            ->middleware('permission:platform.subscriptions.manage')
            ->name('edit');

        Route::get('/{subscription}', 'show')
            ->middleware('permission:platform.subscriptions.view')
            ->name('show');

        Route::put('/{subscription}', 'update')
            ->middleware('permission:platform.subscriptions.manage')
            ->name('update');
    });

    /*
|--------------------------------------------------------------------------
| SaaS Payment Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('saas-payments')
    ->name('saas-payments.')
    ->controller(SaasPaymentController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:platform.payments.view')
            ->name('index');

        Route::get('/{saasPayment}', 'show')
            ->whereNumber('saasPayment')
            ->middleware('permission:platform.payments.view')
            ->name('show');
    });

/*
|--------------------------------------------------------------------------
| Captive Portal Session Monitoring
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('captive-portal-sessions')
    ->name('captive-portal-sessions.')
    ->controller(CaptivePortalSessionController::class)
    ->group(function () {

        Route::get('/', 'index')
            ->middleware('permission:network.management.view')
            ->name('index');

    });


/*
|--------------------------------------------------------------------------
| Voucher Batch Management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('vouchers')
    ->name('vouchers.')
    ->group(function () {

        Route::get('/dashboard', [VoucherDashboardController::class, 'index'])
            ->middleware('role:super_admin')
            ->name('dashboard');

        Route::get('/batches', [VoucherBatchController::class, 'index'])
            ->middleware('permission:voucher.management.view')
            ->name('batches.index');

        Route::get('/batches/create', [VoucherBatchController::class, 'create'])
            ->middleware('permission:voucher.management.manage')
            ->name('batches.create');

        Route::post('/batches', [VoucherBatchController::class, 'store'])
            ->middleware('permission:voucher.management.manage')
            ->name('batches.store');

        Route::get('/batches/{batch}', [VoucherBatchController::class, 'show'])
            ->whereNumber('batch')
            ->middleware('permission:voucher.management.view')
            ->name('batches.show');

        Route::get('/batches/{batch}/print', [VoucherPrintController::class, 'batch'])
            ->whereNumber('batch')
            ->middleware('permission:voucher.management.view')
            ->name('batches.print');

        Route::patch('/batches/{batch}/close', [VoucherBatchController::class, 'close'])
            ->whereNumber('batch')
            ->middleware('permission:voucher.management.manage')
            ->name('batches.close');

        Route::get('/agents', [VoucherAgentController::class, 'index'])
            ->middleware('permission:voucher.agents.view')
            ->name('agents.index');

        Route::post('/agents', [VoucherAgentController::class, 'store'])
            ->middleware('permission:voucher.agents.manage')
            ->name('agents.store');

        Route::patch('/agents/{agent}', [VoucherAgentController::class, 'update'])
            ->whereNumber('agent')
            ->middleware('permission:voucher.agents.manage')
            ->name('agents.update');

        Route::get('/purchases', [VoucherAgentPurchaseController::class, 'index'])
            ->middleware('permission:voucher.agents.view')
            ->name('purchases.index');

        Route::post('/purchases/{purchase}/confirm', [
            VoucherAgentPurchaseController::class,
            'confirm',
        ])
            ->whereNumber('purchase')
            ->middleware('permission:voucher.agents.manage')
            ->name('purchases.confirm');

        Route::post('/purchases/{purchase}/cancel', [
            VoucherAgentPurchaseController::class,
            'cancel',
        ])
            ->whereNumber('purchase')
            ->middleware('permission:voucher.agents.manage')
            ->name('purchases.cancel');

        Route::get('/reports', [VoucherReportController::class, 'index'])
            ->middleware('permission:voucher.reports.view')
            ->name('reports.index');
    });


/*
|--------------------------------------------------------------------------
| Voucher Agent Portal
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'permission:voucher.agent.dashboard',
])
    ->prefix('voucher-agent')
    ->name('voucher-agent.')
    ->controller(VoucherAgentDashboardController::class)
    ->group(function () {

        Route::get('/', 'index')->name('dashboard');

        Route::get('/print', [VoucherPrintController::class, 'agent'])
            ->name('print');

        Route::get('/batches', 'availableBatches')
            ->name('batches');

        Route::post('/purchases', 'requestPurchase')
            ->middleware('throttle:10,1')
            ->name('purchases.store');
    });


require __DIR__.'/auth.php';
