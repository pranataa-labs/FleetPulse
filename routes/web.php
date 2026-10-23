<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DispatcherController;
use App\Http\Controllers\DriverMobileController;
use App\Http\Controllers\PublicTrackingController;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Landing & Portal Home
Route::get('/', function () {
    $activeOrders = \Illuminate\Support\Facades\Schema::hasTable('orders')
        ? Order::withoutGlobalScope('tenant')->with('tenant')->latest()->take(6)->get()
        : collect();
    $tenants = \Illuminate\Support\Facades\Schema::hasTable('tenants')
        ? Tenant::withCount(['fleets', 'drivers', 'orders'])->get()
        : collect();
    return view('welcome', compact('activeOrders', 'tenants'));
})->name('home');

// 2. Authentication & Demo Role Switcher
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/demo-switch/{userId}', [AuthController::class, 'switchDemo'])->name('demo.switch');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 3. Public Customer Live Tracking
Route::get('/track/{token}', [PublicTrackingController::class, 'show'])->name('tracking.show');

// 4. Dispatcher Operations Center
Route::prefix('dispatch')->group(function () {
    Route::get('/', [DispatcherController::class, 'index'])->name('dispatcher.index');
    Route::post('/orders', [DispatcherController::class, 'storeOrder'])->name('dispatcher.orders.store');
    Route::post('/orders/{orderId}/broadcast', [DispatcherController::class, 'dispatchOrder'])->name('dispatcher.orders.dispatch');
    Route::get('/api/drivers', [DispatcherController::class, 'apiFleetGeo'])->name('dispatcher.api.drivers');
});

// 5. Driver Mobile PWA Cockpit
Route::get('/driver', [DriverMobileController::class, 'index'])->name('driver.index');

// 6. Obfuscated Administrative Operations Suite
$adminPrefix = config('app.admin_prefix', 'fleetpulse-ops-mgmt');
Route::prefix($adminPrefix)->middleware(['auth', 'admin.protect'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/pricing/{id}', [AdminController::class, 'updatePricingRule'])->name('admin.pricing.update');
});

// 7. Security Honeypot / Old Admin Trap
Route::any('/admin/{any?}', function () {
    return redirect()->route('home')->with('error', 'Akses ditolak. Anda bukan admin.');
})->where('any', '.*');
