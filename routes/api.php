<?php

use App\Http\Controllers\Api\MobileAdminController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\DriverApiController;
use App\Http\Controllers\OrderMessageController;
use App\Http\Controllers\PublicTrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for FleetPulse Platform
|--------------------------------------------------------------------------
*/

// 1. Public Tracking API
Route::get('/track/{token}', [PublicTrackingController::class, 'status'])->name('api.track.status');

// 2. Dedicated Android Mobile Application APIs (v1)
Route::prefix('v1')->group(function () {
    // Mobile Authentication for Android Courier & Android Admin
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->name('api.v1.auth.login');

    // Android Courier (Driver) App APIs
    Route::prefix('driver')->group(function () {
        Route::post('/toggle-duty', [DriverApiController::class, 'toggleDuty'])->name('api.v1.driver.toggle_duty');
        Route::post('/location', [DriverApiController::class, 'updateLocation'])->name('api.v1.driver.location');
        Route::get('/orders/available', [DriverApiController::class, 'availableOrders'])->name('api.v1.driver.orders.available');
        Route::get('/orders/active', [DriverApiController::class, 'activeOrder'])->name('api.v1.driver.orders.active');
        Route::post('/orders/{orderId}/claim', [DriverApiController::class, 'claimOrder'])->name('api.v1.driver.claim_order');
        Route::post('/orders/{orderId}/status', [DriverApiController::class, 'updateOrderStatus'])->name('api.v1.driver.update_status');
    });

    // Android Admin / Supervisor App APIs
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [MobileAdminController::class, 'dashboard'])->name('api.v1.admin.dashboard');
        Route::get('/drivers', [MobileAdminController::class, 'drivers'])->name('api.v1.admin.drivers');
        Route::get('/orders', [MobileAdminController::class, 'orders'])->name('api.v1.admin.orders');
    });
});

// 3. Backward-Compatible Driver Telemetry & Order Status Endpoints
Route::prefix('driver')->group(function () {
    Route::post('/location', [DriverApiController::class, 'updateLocation'])->name('api.driver.location');
    Route::post('/toggle-duty', [DriverApiController::class, 'toggleDuty'])->name('api.driver.toggle_duty');
    Route::post('/orders/{orderId}/claim', [DriverApiController::class, 'claimOrder'])->name('api.driver.claim_order');
    Route::post('/orders/{orderId}/status', [DriverApiController::class, 'updateOrderStatus'])->name('api.driver.update_status');
});

// 4. Delivery Messages Storage Endpoint
Route::get('/orders/{orderId}/messages', [OrderMessageController::class, 'index'])->name('api.orders.messages.index');
Route::post('/orders/{orderId}/messages', [OrderMessageController::class, 'store'])->name('api.orders.messages.store');


