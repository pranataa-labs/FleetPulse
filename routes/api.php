<?php

use App\Http\Controllers\DriverApiController;
use App\Http\Controllers\OrderMessageController;
use App\Http\Controllers\PublicTrackingController;
use Illuminate\Support\Facades\Route;

// Public Tracking API
Route::get('/track/{token}', [PublicTrackingController::class, 'status'])->name('api.track.status');

// Driver Real-Time Telemetry & Operations
Route::prefix('driver')->group(function () {
    Route::post('/location', [DriverApiController::class, 'updateLocation'])->name('api.driver.location');
    Route::post('/toggle-duty', [DriverApiController::class, 'toggleDuty'])->name('api.driver.toggle_duty');
    Route::post('/orders/{orderId}/claim', [DriverApiController::class, 'claimOrder'])->name('api.driver.claim_order');
    Route::post('/orders/{orderId}/status', [DriverApiController::class, 'updateOrderStatus'])->name('api.driver.update_status');
});

// Transparent 4-Party Delivery Chat (Sender, Courier, Dispatcher, Recipient)
Route::get('/orders/{orderId}/messages', [OrderMessageController::class, 'index'])->name('api.orders.messages.index');
Route::post('/orders/{orderId}/messages', [OrderMessageController::class, 'store'])->name('api.orders.messages.store');

