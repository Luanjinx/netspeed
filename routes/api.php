<?php

use App\Http\Controllers\Api\Admin\AdminCatalogNetworkController;
use App\Http\Controllers\Api\Admin\AdminOperationsController;
use App\Http\Controllers\Api\Admin\AdminSystemController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerAgentController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\NetworkTestController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\TechnicianApiController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Middleware\EnsureAdminApi;
use App\Http\Middleware\EnsureAdminOrTechnicianApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/public/home', [CustomerPortalController::class, 'publicHome']);
Route::get('/public/app-config', [AuthController::class, 'appConfig']);
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
    Route::post('/auth/onesignal-id', [AuthController::class, 'updateOneSignalId']);
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
            Route::get('/withdrawals', [CustomerAgentController::class, 'withdrawalsIndex']);
            Route::post('/withdrawals', [CustomerAgentController::class, 'withdrawalsStore']);
        });
    });

    // Dedicated Admin-Only Endpoints for Flutter Admin App (role === 'admin')
    Route::middleware(EnsureAdminApi::class)->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminApiController::class, 'dashboard']);

        // PPPoE & Auto-Isolir Management
        Route::get('/pppoe', [AdminApiController::class, 'pppoeIndex']);
        Route::post('/pppoe/secret', [AdminApiController::class, 'pppoeCreateSecret']);
        Route::post('/pppoe/toggle-isolir', [AdminApiController::class, 'pppoeToggleIsolir']);
        Route::post('/pppoe/kick', [AdminApiController::class, 'pppoeKick']);
        Route::post('/pppoe/reset-fup', [AdminApiController::class, 'pppoeResetFup']);
        Route::post('/isolir/run', [AdminApiController::class, 'runAutoIsolir']);

        // Hotspot Management & Voucher Orders
        Route::get('/hotspot', [AdminApiController::class, 'hotspotIndex']);
        Route::post('/hotspot/generate', [AdminApiController::class, 'hotspotGenerate']);
        Route::post('/hotspot/kick', [AdminApiController::class, 'hotspotKick']);
        Route::delete('/hotspot/voucher/{id}', [AdminApiController::class, 'hotspotDeleteVoucher']);
        Route::get('/voucher-orders', [AdminApiController::class, 'voucherOrders']);
        Route::post('/voucher-orders/{orderNumber}/approve', [AdminApiController::class, 'approveVoucherOrder']);

        // Customers CRUD + Live / ONT Controls
        Route::get('/customers/meta', [AdminOperationsController::class, 'customerMeta']);
        Route::get('/customers', [AdminOperationsController::class, 'customersIndex']);
        Route::post('/customers', [AdminOperationsController::class, 'customerStore']);
        Route::get('/customers/{id}', [AdminOperationsController::class, 'customerShow']);
        Route::put('/customers/{id}', [AdminOperationsController::class, 'customerUpdate']);
        Route::delete('/customers/{id}', [AdminOperationsController::class, 'customerDestroy']);
        Route::post('/customers/{id}/toggle-status', [AdminOperationsController::class, 'customerToggleStatus']);
        Route::get('/customers/{id}/live-status', [AdminOperationsController::class, 'customerLiveStatus']);
        Route::post('/customers/{id}/ont-reboot', [AdminOperationsController::class, 'customerOntReboot']);
        Route::post('/customers/{id}/ont-wifi', [AdminOperationsController::class, 'customerOntWifi']);
        Route::post('/customers/{id}/generate-invoice', [AdminOperationsController::class, 'customerGenerateInvoice']);

        // Invoices & Payments Verification
        Route::get('/invoices', [AdminOperationsController::class, 'invoicesIndex']);
        Route::post('/invoices/generate-monthly', [AdminOperationsController::class, 'invoiceGenerateMonthly']);
        Route::post('/invoices/{id}/mark-paid', [AdminOperationsController::class, 'invoiceMarkPaid']);
        Route::get('/payments', [AdminOperationsController::class, 'paymentsIndex']);
        Route::post('/payments/{id}/verify', [AdminOperationsController::class, 'paymentVerify']);

        // Work Orders (SPK) & Support Tickets
        Route::get('/work-orders', [AdminOperationsController::class, 'workOrdersIndex']);
        Route::post('/work-orders', [AdminOperationsController::class, 'workOrderStore']);
        Route::post('/work-orders/{id}/assign', [AdminOperationsController::class, 'workOrderAssign']);
        Route::get('/tickets', [AdminOperationsController::class, 'ticketsIndex']);
        Route::get('/tickets/{id}', [AdminOperationsController::class, 'ticketShow']);
        Route::post('/tickets/{id}/reply', [AdminOperationsController::class, 'ticketReply']);

        // Mitra Agents, Approval, Top-Up & Agent Payment Point
        Route::get('/agents', [AdminOperationsController::class, 'agentsIndex']);
        Route::post('/agents/{id}/approve', [AdminOperationsController::class, 'agentApprove']);
        Route::post('/agents/{id}/reject', [AdminOperationsController::class, 'agentReject']);
        Route::post('/agents/{id}/top-up', [AdminOperationsController::class, 'agentTopUp']);
        Route::post('/agents/{id}/toggle-active', [AdminOperationsController::class, 'agentToggleActive']);
        Route::get('/agents/{id}/transactions', [AdminOperationsController::class, 'agentTransactions']);
        Route::get('/agent-payment/search', [AdminOperationsController::class, 'agentPaymentSearch']);
        Route::post('/agent-payment/pay', [AdminOperationsController::class, 'agentPayInvoice']);
        Route::get('/agent-withdrawals', [AdminOperationsController::class, 'agentWithdrawalsIndex']);
        Route::post('/agent-withdrawals/{id}/approve', [AdminOperationsController::class, 'agentWithdrawalApprove']);
        Route::post('/agent-withdrawals/{id}/reject', [AdminOperationsController::class, 'agentWithdrawalReject']);

        // OneSignal Push Notification
        Route::post('/onesignal/send', [AdminApiController::class, 'onesignalSend']);
        Route::get('/onesignal/history', [AdminApiController::class, 'onesignalHistory']);

        // Package Registration & Upgrade Approval Requests
        Route::get('/package-requests', [AdminApiController::class, 'packageRequests']);
        Route::post('/package-requests/{id}/approve', [AdminApiController::class, 'approvePackageRequest']);
        Route::post('/package-requests/{id}/reject', [AdminApiController::class, 'rejectPackageRequest']);

        // Catalog: Internet Packages & Promos
        Route::get('/packages', [AdminCatalogNetworkController::class, 'packagesIndex']);
        Route::post('/packages', [AdminCatalogNetworkController::class, 'packageStore']);
        Route::put('/packages/{id}', [AdminCatalogNetworkController::class, 'packageUpdate']);
        Route::delete('/packages/{id}', [AdminCatalogNetworkController::class, 'packageDestroy']);
        Route::post('/packages/sync-mikrotik', [AdminCatalogNetworkController::class, 'packagesSyncMikrotik']);

        Route::get('/promos', [AdminCatalogNetworkController::class, 'promosIndex']);
        Route::post('/promos', [AdminCatalogNetworkController::class, 'promoStore']);
        Route::put('/promos/{id}', [AdminCatalogNetworkController::class, 'promoUpdate']);
        Route::delete('/promos/{id}', [AdminCatalogNetworkController::class, 'promoDestroy']);

        // Infrastructure: Routers, GenieACS & ODPs / Map
        Route::get('/routers', [AdminCatalogNetworkController::class, 'routersIndex']);
        Route::post('/routers', [AdminCatalogNetworkController::class, 'routerStore']);
        Route::put('/routers/{id}', [AdminCatalogNetworkController::class, 'routerUpdate']);
        Route::delete('/routers/{id}', [AdminCatalogNetworkController::class, 'routerDestroy']);
        Route::post('/routers/{id}/test', [AdminCatalogNetworkController::class, 'routerTestConnection']);
        Route::post('/routers/{id}/sync-profiles', [AdminCatalogNetworkController::class, 'routerSyncProfiles']);

        Route::get('/genieacs-servers', [AdminCatalogNetworkController::class, 'genieacsIndex']);
        Route::post('/genieacs-servers', [AdminCatalogNetworkController::class, 'genieacsStore']);
        Route::put('/genieacs-servers/{id}', [AdminCatalogNetworkController::class, 'genieacsUpdate']);
        Route::delete('/genieacs-servers/{id}', [AdminCatalogNetworkController::class, 'genieacsDestroy']);
        Route::post('/genieacs-servers/{id}/test', [AdminCatalogNetworkController::class, 'genieacsTest']);
        Route::get('/genieacs-servers/{id}/devices', [AdminCatalogNetworkController::class, 'genieacsDevices']);
        Route::post('/genieacs-servers/{serverId}/device-action', [AdminCatalogNetworkController::class, 'genieacsDeviceAction']);

        Route::get('/odps', [AdminCatalogNetworkController::class, 'odpsIndex']);
        Route::post('/odps', [AdminCatalogNetworkController::class, 'odpStore']);
        Route::put('/odps/{id}', [AdminCatalogNetworkController::class, 'odpUpdate']);
        Route::delete('/odps/{id}', [AdminCatalogNetworkController::class, 'odpDestroy']);
        Route::get('/network-map', [AdminCatalogNetworkController::class, 'networkMapData']);

        // System, Staff (Admin & Technicians), Site Pages, Gateways, Settings & Updates
        Route::get('/staff', [AdminSystemController::class, 'staffIndex']);
        Route::post('/staff', [AdminSystemController::class, 'staffStore']);
        Route::put('/staff/{id}', [AdminSystemController::class, 'staffUpdate']);
        Route::delete('/staff/{id}', [AdminSystemController::class, 'staffDestroy']);

        Route::get('/pages', [AdminSystemController::class, 'pagesIndex']);
        Route::post('/pages', [AdminSystemController::class, 'pageStore']);
        Route::put('/pages/{id}', [AdminSystemController::class, 'pageUpdate']);
        Route::delete('/pages/{id}', [AdminSystemController::class, 'pageDestroy']);

        Route::get('/payment-gateways', [AdminSystemController::class, 'gatewaysIndex']);
        Route::put('/payment-gateways/{id}', [AdminSystemController::class, 'gatewayUpdate']);

        Route::get('/settings', [AdminSystemController::class, 'settingsGet']);
        Route::post('/settings', [AdminSystemController::class, 'settingsUpdate']);
        Route::post('/settings/test-whatsapp', [AdminSystemController::class, 'testWhatsApp']);
        Route::post('/settings/test-telegram', [AdminSystemController::class, 'testTelegram']);

        Route::get('/system-update', [AdminSystemController::class, 'systemUpdateStatus']);
        Route::post('/system-update/run', [AdminSystemController::class, 'systemUpdateRun']);
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
