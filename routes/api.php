<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payments\FastLipaWebhookController;

Route::post('/webhooks/fastlipa', [
    FastLipaWebhookController::class,
    'handle',
])->name('webhooks.fastlipa');