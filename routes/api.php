<?php

use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\NetworkTestController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/public/home', [CustomerPortalController::class, 'publicHome']);
Route::get('/public/promos/{id}', [CustomerPortalController::class, 'publicPromoDetail']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/payment/webhook', [PaymentWebhookController::class, 'handle']);
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Network Ping & Speedtest for Flutter
Route::prefix('network')->group(function () {
    Route::get('/ping', [NetworkTestController::class, 'ping']);
    Route::get('/speedtest-download', [NetworkTestController::class, 'downloadTest']);
    Route::post('/speedtest-upload', [NetworkTestController::class, 'uploadTest']);
});

// Authenticated customer & technician routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Customer Portal Endpoints for Flutter
    Route::prefix('customer')->group(function () {
        Route::get('/profile', [CustomerPortalController::class, 'profile']);
        Route::get('/invoices', [CustomerPortalController::class, 'invoices']);
        Route::get('/invoices/{id}', [CustomerPortalController::class, 'invoiceDetail']);
        Route::post('/invoices/{id}/proof', [CustomerPortalController::class, 'uploadProof']);
        Route::post('/fcm-token', [CustomerPortalController::class, 'updateFcmToken']);
        Route::post('/onesignal-id', [CustomerPortalController::class, 'updateOneSignalId']);

        // GenieACS TR-069 ONT Management & Topology
        Route::get('/ont-status', [CustomerPortalController::class, 'ontStatus']);
        Route::post('/change-wifi', [CustomerPortalController::class, 'changeWifi']);
        Route::get('/topology', [CustomerPortalController::class, 'topology']);
        Route::post('/reboot-ont', [CustomerPortalController::class, 'rebootOnt']);
        Route::get('/diagnose', [CustomerPortalController::class, 'diagnose']);

        // Trouble Tickets
        Route::get('/tickets', [CustomerPortalController::class, 'tickets']);
        Route::post('/tickets', [CustomerPortalController::class, 'createTicket']);
    });

    // Admin & Technician Endpoints for Flutter Admin App
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminApiController::class, 'dashboard']);

        // PPPoE Management
        Route::get('/pppoe', [AdminApiController::class, 'pppoeIndex']);
        Route::post('/pppoe/secret', [AdminApiController::class, 'pppoeCreateSecret']);
        Route::post('/pppoe/toggle-isolir', [AdminApiController::class, 'pppoeToggleIsolir']);
        Route::post('/pppoe/kick', [AdminApiController::class, 'pppoeKick']);
        Route::post('/pppoe/reset-fup', [AdminApiController::class, 'pppoeResetFup']);

        // Hotspot Management
        Route::get('/hotspot', [AdminApiController::class, 'hotspotIndex']);
        Route::post('/hotspot/generate', [AdminApiController::class, 'hotspotGenerate']);
        Route::post('/hotspot/kick', [AdminApiController::class, 'hotspotKick']);
        Route::delete('/hotspot/voucher/{id}', [AdminApiController::class, 'hotspotDeleteVoucher']);

        // Customers & Invoices
        Route::get('/customers', [AdminApiController::class, 'customers']);
        Route::get('/invoices', [AdminApiController::class, 'invoices']);

        // OneSignal Push Notification
        Route::post('/onesignal/send', [AdminApiController::class, 'onesignalSend']);
        Route::get('/onesignal/history', [AdminApiController::class, 'onesignalHistory']);
    });

    // Admin & Technician Endpoints for Flutter Admin App
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminApiController::class, 'dashboard']);

        // PPPoE Management
        Route::get('/pppoe', [AdminApiController::class, 'pppoeIndex']);
        Route::post('/pppoe/secret', [AdminApiController::class, 'pppoeCreateSecret']);
        Route::post('/pppoe/toggle-isolir', [AdminApiController::class, 'pppoeToggleIsolir']);
        Route::post('/pppoe/kick', [AdminApiController::class, 'pppoeKick']);
        Route::post('/pppoe/reset-fup', [AdminApiController::class, 'pppoeResetFup']);

        // Hotspot Management
        Route::get('/hotspot', [AdminApiController::class, 'hotspotIndex']);
        Route::post('/hotspot/generate', [AdminApiController::class, 'hotspotGenerate']);
        Route::post('/hotspot/kick', [AdminApiController::class, 'hotspotKick']);
        Route::delete('/hotspot/voucher/{id}', [AdminApiController::class, 'hotspotDeleteVoucher']);

        // Customers & Invoices
        Route::get('/customers', [AdminApiController::class, 'customers']);
        Route::get('/invoices', [AdminApiController::class, 'invoices']);

        // OneSignal Push Notification
        Route::post('/onesignal/send', [AdminApiController::class, 'onesignalSend']);
        Route::get('/onesignal/history', [AdminApiController::class, 'onesignalHistory']);
    });
});
