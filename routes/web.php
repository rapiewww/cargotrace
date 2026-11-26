<?php

use App\Http\Controllers\AnalyticsWebController;
use App\Http\Controllers\AuditLogWebController;
use App\Http\Controllers\CarrierLocationWebController;
use App\Http\Controllers\CurrencyWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionWebController;
use App\Http\Controllers\NewsWebController;
use App\Http\Controllers\NotificationWebController;
use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\SettingsWebController;
use App\Http\Controllers\ShipmentImportWebController;
use App\Http\Controllers\ShipmentWebController;
use App\Http\Controllers\UserManagementWebController;
use App\Http\Controllers\WeatherWebController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WelcomeController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Landing & Live Tracking
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/locale/{locale}', function (Request $request, string $locale) {
    if (! in_array($locale, SetLocale::SUPPORTED_LOCALES, true)) {
        abort(404);
    }

    $request->session()->put('locale', $locale);

    return redirect()->back(fallback: route('welcome'));
})->name('locale.switch');
Route::get('/track/{tracking_number}', [PublicTrackingController::class, 'show'])
    ->name('public.track')
    ->middleware('throttle:30,1');

// Guest Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register'])->middleware('throttle:10,1');
});

// Authenticated Session Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/map-data', [DashboardController::class, 'mapData'])->name('dashboard.map-data');

    // Fleet & Consignment Performance Analytics
    Route::get('/analytics', [AnalyticsWebController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/export', [AnalyticsWebController::class, 'export'])->name('analytics.export');

    // Dedicated Maritime Intelligence Hubs
    Route::get('/weather', [WeatherWebController::class, 'index'])->name('weather.index');
    Route::get('/currency', [CurrencyWebController::class, 'index'])->name('currency.index');
    Route::get('/news', [NewsWebController::class, 'index'])->name('news.index');

    // Shipments
    Route::get('/shipments', [ShipmentWebController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentWebController::class, 'create'])->name('shipments.create')
        ->middleware('role:admin,operator');
    Route::post('/shipments', [ShipmentWebController::class, 'store'])->name('shipments.store')
        ->middleware('role:admin,operator');
    Route::get('/shipments/export/csv', [ShipmentWebController::class, 'exportCsv'])->name('shipments.export.csv');
    Route::get('/shipments/import/sample', [ShipmentImportWebController::class, 'sample'])->name('shipments.import.sample');
    Route::get('/shipments/import', [ShipmentImportWebController::class, 'index'])->name('shipments.import.index');
    Route::post('/shipments/import/preview', [ShipmentImportWebController::class, 'preview'])->name('shipments.import.preview');
    Route::post('/shipments/import/confirm', [ShipmentImportWebController::class, 'confirm'])->name('shipments.import.confirm');
    Route::get('/shipments/{shipment}', [ShipmentWebController::class, 'show'])->name('shipments.show');
    Route::get('/shipments/{shipment}/edit', [ShipmentWebController::class, 'edit'])->name('shipments.edit')
        ->middleware('role:admin,operator');
    Route::put('/shipments/{shipment}', [ShipmentWebController::class, 'update'])->name('shipments.update')
        ->middleware('role:admin,operator');
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
        Route::put('/carriers/{carrier}', [CarrierLocationWebController::class, 'updateCarrier'])->name('operations.carriers.update')
            ->middleware('role:admin');
        Route::delete('/carriers/{carrier}', [CarrierLocationWebController::class, 'destroyCarrier'])->name('operations.carriers.destroy')
            ->middleware('role:admin');

        Route::get('/locations', [CarrierLocationWebController::class, 'locations'])->name('operations.locations');
        Route::post('/locations', [CarrierLocationWebController::class, 'storeLocation'])->name('operations.locations.store')
            ->middleware('role:admin');
        Route::put('/locations/{location}', [CarrierLocationWebController::class, 'updateLocation'])->name('operations.locations.update')
            ->middleware('role:admin');
        Route::delete('/locations/{location}', [CarrierLocationWebController::class, 'destroyLocation'])->name('operations.locations.destroy')
            ->middleware('role:admin');
    });

    // User Management (Admin only)
    Route::middleware('role:admin')->prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserManagementWebController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementWebController::class, 'create'])->name('create');
        Route::post('/', [UserManagementWebController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserManagementWebController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementWebController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementWebController::class, 'destroy'])->name('destroy');
    });

    // Settings (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/settings', [SettingsWebController::class, 'index'])->name('admin.settings');
        Route::post('/admin/settings', [SettingsWebController::class, 'update'])->name('admin.settings.update');
        Route::post('/admin/settings/reevaluate', [SettingsWebController::class, 'reevaluate'])->name('admin.settings.reevaluate');
    });

    // Audit Logs (Admin only)
    Route::get('/audit-logs', [AuditLogWebController::class, 'index'])->name('operations.audit-logs')
        ->middleware('role:admin');

    // Notifications
    Route::get('/notifications', [NotificationWebController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationWebController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationWebController::class, 'markAllAsRead'])->name('notifications.read-all');
});
