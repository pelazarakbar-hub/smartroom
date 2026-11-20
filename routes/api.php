<?php

use App\Http\Controllers\Api\DeviceApiController;
use Illuminate\Support\Facades\Route;

// Dipakai perangkat IoT. Semua alamat diawali /api (misalnya POST /api/device/readings).
Route::middleware('throttle:120,1')->prefix('device')->group(function () {
    Route::post('/readings', [DeviceApiController::class, 'storeReading']);
    Route::get('/state', [DeviceApiController::class, 'state']);
    Route::post('/state', [DeviceApiController::class, 'reportState']);
});
