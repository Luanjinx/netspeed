<?php

use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerAgentController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\NetworkTestController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\TechnicianApiController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Middleware\EnsureAdminOrTechnicianApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/public/home', [CustomerPortalController::class, 'publicHome']);
Route::get('/public/promos/{id}', [CustomerPortalController::class, 'publicPromoDetail']);
Route::get('/public/pages', [CustomerPortalController::class, 'publicPages']);
Route::get('/public/pages/{slug}', [CustomerPortalController::class, 'publicPageDetail']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/payment/webhook', [PaymentWebhookController::class, 'handle']);
Route::get('/payment/gateways', [CustomerPortalController::class, 'paymentGateways']);
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Hotspot Voucher purchase & status check (Public / Guest)
Route::post('/public/voucher/order', [CustomerPortalController::class, 'voucherOrder'])->middleware('throttle:15,1');
Route::post('/public/voucher/order/{orderNumber}/confirm', [CustomerPortalController::class, 'voucherOrderConfirm'])->middleware('throttle:20,1');
Route::get('/public/voucher/history', [CustomerPortalController::class, 'voucherHistory'])->middleware('throttle:20,1');

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
        Route::get('/payment-gateways', [CustomerPortalController::class, 'paymentGateways']);
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
        Route::get('/tickets/{id}', [CustomerPortalController::class, 'ticketDetail']);
        Route::post('/tickets/{id}/reply', [CustomerPortalController::class, 'replyTicket']);

        // PPPoE Package Upgrade with Prorata & Approval Requests
        Route::get('/package/upgrade-estimate', [CustomerPortalController::class, 'packageUpgradeEstimate']);
        Route::post('/package/upgrade', [CustomerPortalController::class, 'packageUpgrade']);
        Route::get('/package-requests', [CustomerPortalController::class, 'packageRequests']);

        // Hotspot Voucher Order for Logged-In Customer
        Route::post('/voucher/order', [CustomerPortalController::class, 'voucherOrder']);
        Route::post('/voucher/order/{orderNumber}/confirm', [CustomerPortalController::class, 'voucherOrderConfirm']);
        Route::get('/vouchers', [CustomerPortalController::class, 'voucherHistory']);

        // Customer Dual-Role Agent Registration & Native Cashier Portal
        Route::prefix('agent')->group(function () {
            Route::get('/status', [CustomerAgentController::class, 'status']);
            Route::post('/register', [CustomerAgentController::class, 'register']);
            Route::get('/search-invoices', [CustomerAgentController::class, 'searchInvoices']);
            Route::post('/pay-invoice', [CustomerAgentController::class, 'payInvoice']);
            Route::get('/transactions', [CustomerAgentController::class, 'transactions']);
        });
    });

    // Admin & Technician Endpoints for Flutter Admin App
    Route::middleware(EnsureAdminOrTechnicianApi::class)->prefix('admin')->group(function () {
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
        Route::post('/voucher-orders/{orderNumber}/approve', [AdminApiController::class, 'approveVoucherOrder']);

        // Customers & Invoices
        Route::get('/customers', [AdminApiController::class, 'customers']);
        Route::get('/invoices', [AdminApiController::class, 'invoices']);

        // OneSignal Push Notification
        Route::post('/onesignal/send', [AdminApiController::class, 'onesignalSend']);
        Route::get('/onesignal/history', [AdminApiController::class, 'onesignalHistory']);

        // Package Registration & Upgrade Approval Requests
        Route::get('/package-requests', [AdminApiController::class, 'packageRequests']);
        Route::post('/package-requests/{id}/approve', [AdminApiController::class, 'approvePackageRequest']);
        Route::post('/package-requests/{id}/reject', [AdminApiController::class, 'rejectPackageRequest']);
    });

    // Dedicated Field Technician App Endpoints
    Route::middleware(EnsureAdminOrTechnicianApi::class)->prefix('technician')->group(function () {
        Route::get('/dashboard', [TechnicianApiController::class, 'dashboard']);

        // Work Orders (SPK)
        Route::get('/work-orders/{id}', [TechnicianApiController::class, 'showWorkOrder']);
        Route::post('/work-orders/{id}/start', [TechnicianApiController::class, 'startWorkOrder']);
        Route::post('/work-orders/{id}/complete', [TechnicianApiController::class, 'completeWorkOrder']);

        // Live Connectivity & GenieACS ONT Control
        Route::get('/customers/{customerId}/live-status', [TechnicianApiController::class, 'checkLiveStatus']);
        Route::post('/ont/wifi', [TechnicianApiController::class, 'updateOntWifi']);
        Route::post('/ont/reboot', [TechnicianApiController::class, 'rebootOnt']);

        // Pool Tickets & Replies
        Route::get('/pool-tickets', [TechnicianApiController::class, 'poolTickets']);
        Route::post('/pool-tickets/{id}/claim', [TechnicianApiController::class, 'claimTicket']);
        Route::post('/tickets/{id}/reply', [TechnicianApiController::class, 'replyTicket']);

        // Package Approvals
        Route::get('/package-requests', [AdminApiController::class, 'packageRequests']);
        Route::post('/package-requests/{id}/approve', [AdminApiController::class, 'approvePackageRequest']);
        Route::post('/package-requests/{id}/reject', [AdminApiController::class, 'rejectPackageRequest']);

        // History, Network Monitoring & Field PSB Registration
        Route::get('/history', [TechnicianApiController::class, 'history']);
        Route::get('/monitoring', [TechnicianApiController::class, 'monitoring']);
        Route::get('/psb/options', [TechnicianApiController::class, 'psbOptions']);
        Route::get('/odps/{odpId}/ports', [TechnicianApiController::class, 'getOdpPorts']);
        Route::post('/psb', [TechnicianApiController::class, 'storePsbCustomer']);

        // Technician Account Password
        Route::post('/profile/password', [TechnicianApiController::class, 'updatePassword']);
    });
});
