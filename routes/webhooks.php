<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use NumaxLab\Lunar\Redsys\Http\Controllers\WebhookController;

Route::post('redsys/webhook', WebhookController::class)
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('lunar.redsys.webhook');
