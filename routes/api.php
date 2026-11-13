<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CurrencyApiController;
use App\Http\Controllers\Api\ExceptionApiController;
use App\Http\Controllers\Api\MasterApiController;
use App\Http\Controllers\Api\ShipmentApiController;
use App\Http\Controllers\Api\TrackingApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Prefix: /api)
|--------------------------------------------------------------------------
*/

// Public Authentication & Utilities
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/currency/currencies', [CurrencyApiController::class, 'currencies']);
Route::get('/currency/currencies', [CurrencyApiController::class, 'currencies']);
Route::match(['get', 'post'], '/currency/convert', [CurrencyApiController::class, 'convert']);
Route::get('/currency/history', [CurrencyApiController::class, 'history']);
Route::get('/currency/history', [CurrencyApiController::class, 'history']);

// Authenticated Endpoints (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Shipments
    Route::get('/shipments', [ShipmentApiController::class, 'index']);
    Route::get('/shipments/{shipment}', [ShipmentApiController::class, 'show']);
    Route::post('/shipments', [ShipmentApiController::class, 'store'])
        ->middleware('role:admin,operator');
    Route::match(['put', 'patch'], '/shipments/{shipment}', [ShipmentApiController::class, 'update'])
        ->middleware('role:admin,operator');
    Route::delete('/shipments/{shipment}', [ShipmentApiController::class, 'destroy'])
        ->middleware('role:admin');

    // Tracking Events
    Route::get('/shipments/{shipment}/tracking', [TrackingApiController::class, 'index']);
    Route::post('/shipments/{shipment}/tracking', [TrackingApiController::class, 'store'])
        ->middleware('role:admin,operator');

    // Exceptions
    Route::get('/shipments/{shipment}/exceptions', [ExceptionApiController::class, 'index']);
    Route::post('/shipments/{shipment}/exceptions', [ExceptionApiController::class, 'store'])
        ->middleware('role:admin,operator');
    Route::patch('/exceptions/{exception}', [ExceptionApiController::class, 'updateStatus'])
        ->middleware('role:admin,operator');

    // Master Data & External Integrations
    Route::get('/carriers', [MasterApiController::class, 'carriers']);
    Route::post('/carriers', [MasterApiController::class, 'storeCarrier'])
        ->middleware('role:admin');

    Route::get('/locations', [MasterApiController::class, 'locations']);
    Route::post('/locations', [MasterApiController::class, 'storeLocation'])
        ->middleware('role:admin');

    Route::get('/integrations/weather', [MasterApiController::class, 'weather']);
    Route::get('/integrations/currency', [MasterApiController::class, 'currency']);
    Route::get('/integrations/news', [MasterApiController::class, 'news']);
});
