<?php

use App\Modules\Payment\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Module API Routes (mounted by the registry at
| /api/v1/payment/* with auth.any + tenant).
|--------------------------------------------------------------------------
|
| The webhook is the only surface and the only money-verification path:
| Razorpay calls it with a signature over the raw body, no session and
| no tenant header exist — it opts out of auth.any + tenant (doc 01 §1)
| and verifies HMAC-SHA256 against RAZORPAY_WEBHOOK_SECRET itself,
| fail-closed. Throttled below the tier defaults (a flood of webhooks is
| either a replay attack or a provider incident — either way ops should
| look, not silently absorb).
*/

Route::post('webhook', PaymentWebhookController::class)
    ->withoutMiddleware(['auth.any', 'tenant'])
    ->middleware('throttle:30,1')
    ->name('webhook');
