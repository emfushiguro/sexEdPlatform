<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PersonaWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group.
|
*/

// Location data endpoints
Route::get('/cities/{provinceCode}', [LocationController::class, 'getCities']);
Route::get('/barangays/{cityCode}', [LocationController::class, 'getBarangays']);

Route::post('/webhooks/persona', [PersonaWebhookController::class, 'store'])
    ->middleware(['persona.webhook', 'throttle:60,1'])
    ->name('api.webhooks.persona');
