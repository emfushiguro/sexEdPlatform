<?php

use App\Http\Controllers\Api\LocationController;
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
