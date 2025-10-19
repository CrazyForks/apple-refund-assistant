<?php

use Illuminate\Support\Facades\Route;

Route::post('/v1/apps/{id}/webhook', [\App\Http\Controllers\Api\WebhookController::class, 'store']);
