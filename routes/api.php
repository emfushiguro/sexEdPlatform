<?php

use App\Http\Controllers\Api\ClinicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public clinic API endpoints
Route::prefix('clinics')->name('api.clinics.')->group(function () {
    Route::get('/', [ClinicController::class, 'index'])->name('index');
    Route::get('/search', [ClinicController::class, 'search'])->name('search');
    Route::get('/statistics', [ClinicController::class, 'statistics'])->name('statistics');
    Route::get('/{clinic}', [ClinicController::class, 'show'])->name('show');
    Route::get('/barangays/{cityCode}', [LocationController::class, 'getBarangays']);
});