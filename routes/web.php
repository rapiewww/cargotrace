<?php

use App\Http\Controllers\AuditLogWebController;
use App\Http\Controllers\CarrierLocationWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionWebController;
use App\Http\Controllers\NotificationWebController;
use App\Http\Controllers\ShipmentWebController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Landing & Live Tracking
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Guest Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register']);
});

// Authenticated Session Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shipments
    Route::get('/shipments', [ShipmentWebController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentWebController::class, 'create'])->name('shipments.create')
        ->middleware('role:admin,operator');
    Route::post('/shipments', [ShipmentWebController::class, 'store'])->name('shipments.store')
        ->middleware('role:admin,operator');
    Route::get('/shipments/{shipment}', [ShipmentWebController::class, 'show'])->name('shipments.show');
    Route::patch('/shipments/{shipment}/status', [ShipmentWebController::class, 'updateStatus'])->name('shipments.update-status')
        ->middleware('role:admin,operator');
    Route::post('/shipments/{shipment}/tracking', [ShipmentWebController::class, 'addTrackingEvent'])->name('shipments.add-tracking')
        ->middleware('role:admin,operator');
    Route::post('/shipments/{shipment}/exceptions', [ShipmentWebController::class, 'reportException'])->name('shipments.report-exception')
        ->middleware('role:admin,operator');
    Route::delete('/shipments/{shipment}', [ShipmentWebController::class, 'destroy'])->name('shipments.destroy')
        ->middleware('role:admin');

    // Exceptions Management (Admin & Operator)
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/exceptions', [ExceptionWebController::class, 'index'])->name('operations.exceptions');
        Route::patch('/exceptions/{exception}', [ExceptionWebController::class, 'update'])->name('operations.exceptions.update');

        // Carriers & Locations
        Route::get('/carriers', [CarrierLocationWebController::class, 'carriers'])->name('operations.carriers');
        Route::post('/carriers', [CarrierLocationWebController::class, 'storeCarrier'])->name('operations.carriers.store')
            ->middleware('role:admin');

        Route::get('/locations', [CarrierLocationWebController::class, 'locations'])->name('operations.locations');
        Route::post('/locations', [CarrierLocationWebController::class, 'storeLocation'])->name('operations.locations.store')
            ->middleware('role:admin');
    });

    // Audit Logs (Admin only)
    Route::get('/audit-logs', [AuditLogWebController::class, 'index'])->name('operations.audit-logs')
        ->middleware('role:admin');

    // Notifications
    Route::post('/notifications/{notification}/read', [NotificationWebController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationWebController::class, 'markAllAsRead'])->name('notifications.read-all');
});
