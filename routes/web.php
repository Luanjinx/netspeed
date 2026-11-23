<?php

use App\Http\Controllers\Agent\AgentPortalController;
use App\Http\Controllers\Customer\CustomerAuthController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\NetworkMapController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\Technician\TechnicianPortalController;
use App\Http\Middleware\EnsureAgentUser;
use App\Http\Middleware\EnsureCustomerUser;
use App\Http\Middleware\EnsureTechnicianUser;
use App\Models\HotspotVoucher;
use App\Models\IspSetting;
use Illuminate\Support\Facades\Route;

// Web Installer Wizard Routes
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/step1', [InstallController::class, 'step1'])->name('step1');
    Route::post('/fix-permissions', [InstallController::class, 'fixPermissions'])->name('fix_permissions');
    Route::get('/step2', [InstallController::class, 'step2'])->name('step2');
    Route::post('/test-db', [InstallController::class, 'testDb'])->name('test_db');
    Route::post('/step2', [InstallController::class, 'saveStep2'])->name('step2.save');
    Route::get('/step3', [InstallController::class, 'step3'])->name('step3');
    Route::post('/migrate', [InstallController::class, 'runMigrate'])->name('migrate');
    Route::post('/migrate-direct', [InstallController::class, 'runMigrateDirect'])->name('migrate_direct');
    Route::get('/step4', [InstallController::class, 'step4'])->name('step4');
    Route::post('/step4', [InstallController::class, 'saveStep4'])->name('step4.save');
    Route::get('/step5', [InstallController::class, 'step5'])->name('step5');
    Route::post('/finish', [InstallController::class, 'finish'])->name('finish');
});

// Public Landing Page & Self-Service
Route::get('/', [LandingPageController::class, 'index'])->name('landing');
Route::get('/page/{slug}', [LandingPageController::class, 'showPage'])->name('page.show');
Route::post('/cek-tagihan', [LandingPageController::class, 'checkBill'])->name('landing.check-bill');
Route::post('/daftar-langganan', [LandingPageController::class, 'registerLead'])->name('landing.register');

// Customer Web Portal Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('login');
    Route::get('/customer/login', fn () => redirect()->route('login'))->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('customer.login.submit');
});

Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

// Customer Protected Portal Routes
Route::middleware(['auth', EnsureCustomerUser::class])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'dashboard'])->name('dashboard');
    Route::post('/change-wifi', [CustomerDashboardController::class, 'changeWifi'])->name('change-wifi');
    Route::post('/reboot-ont', [CustomerDashboardController::class, 'rebootOnt'])->name('reboot-ont');
    Route::post('/invoice/{id}/proof', [CustomerDashboardController::class, 'uploadProof'])->name('invoice.proof');
    Route::get('/tickets', [CustomerDashboardController::class, 'tickets'])->name('tickets');
    Route::post('/tickets', [CustomerDashboardController::class, 'storeTicket'])->name('tickets.store');
    Route::get('/profile', [CustomerDashboardController::class, 'profile'])->name('profile');
    Route::post('/profile/password', [CustomerDashboardController::class, 'updatePassword'])->name('profile.password');
});

// Technician Web Portal Routes
Route::middleware(['auth', EnsureTechnicianUser::class])->prefix('teknisi')->name('technician.')->group(function () {
    Route::get('/dashboard', [TechnicianPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/tugas/{id}', [TechnicianPortalController::class, 'showWorkOrder'])->name('work-order');
    Route::post('/tugas/{id}/start', [TechnicianPortalController::class, 'startWorkOrder'])->name('work-order.start');
    Route::post('/tugas/{id}/complete', [TechnicianPortalController::class, 'completeWorkOrder'])->name('work-order.complete');
    Route::get('/cek-koneksi/{customerId}', [TechnicianPortalController::class, 'checkLiveStatus'])->name('check-status');

    // Pool Tiket & Klaim Mandiri
    Route::get('/pool-tiket', [TechnicianPortalController::class, 'poolTickets'])->name('pool-tickets');
    Route::post('/pool-tiket/{id}/claim', [TechnicianPortalController::class, 'claimTicket'])->name('claim-ticket');

    // Riwayat Penanganan
    Route::get('/riwayat', [TechnicianPortalController::class, 'history'])->name('history');

    // Monitoring Sistem Ringkas
    Route::get('/monitoring', [TechnicianPortalController::class, 'monitoring'])->name('monitoring');

    // Pasang Baru (PSB Lapangan)
    Route::get('/pasang-baru', [TechnicianPortalController::class, 'createCustomer'])->name('create-customer');
    Route::post('/pasang-baru', [TechnicianPortalController::class, 'storeCustomer'])->name('store-customer');
    Route::get('/ajax/odp-ports/{odpId}', [TechnicianPortalController::class, 'getOdpPorts'])->name('ajax.odp-ports');

    // Kontrol GenieACS ONT
    Route::post('/ont/wifi', [TechnicianPortalController::class, 'updateOntWifi'])->name('ont.wifi');
    Route::post('/ont/reboot', [TechnicianPortalController::class, 'rebootOnt'])->name('ont.reboot');

    // Balasan Tiket Gangguan
    Route::post('/tiket/{id}/reply', [TechnicianPortalController::class, 'replyTicket'])->name('ticket.reply');

    Route::get('/profil', [TechnicianPortalController::class, 'profile'])->name('profile');
    Route::post('/profil/password', [TechnicianPortalController::class, 'updatePassword'])->name('profile.password');
});

// Agent Web Portal Routes
Route::middleware(['auth', EnsureAgentUser::class])->prefix('agen')->name('agent.')->group(function () {
    Route::get('/dashboard', [AgentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/loket', [AgentPortalController::class, 'paymentPoint'])->name('payment-point');
    Route::get('/ajax/search-invoices', [AgentPortalController::class, 'searchInvoices'])->name('ajax.search-invoices');
    Route::post('/ajax/pay-invoice', [AgentPortalController::class, 'processPayment'])->name('ajax.pay-invoice');
    Route::get('/riwayat', [AgentPortalController::class, 'history'])->name('history');
    Route::get('/profil', [AgentPortalController::class, 'profile'])->name('profile');
    Route::post('/profil/password', [AgentPortalController::class, 'updatePassword'])->name('profile.password');
});

// Network map endpoints for Admin and Tech
Route::get('/admin/map-data', [NetworkMapController::class, 'getMapData'])->name('network.map.data');
Route::post('/admin/map-save-cable', [NetworkMapController::class, 'saveCablePath'])->name('network.map.save-cable');
Route::get('/admin/map-traffic/{id}', [NetworkMapController::class, 'getCustomerTraffic'])->name('network.map.traffic');

// Technician Field Map (Mobile-responsive web interface)
Route::get('/tech/map', function () {
    $office = IspSetting::getOfficeLocation();

    return view('tech.map', compact('office'));
})->name('tech.map');

// Printable routes
Route::get('/print/invoice/{id}', [PrintController::class, 'invoice'])->name('print.invoice');
Route::get('/print/thermal/{id}', [PrintController::class, 'thermal'])->name('print.thermal');

Route::get('/print/vouchers/{batch}', function (string $batch) {
    $vouchers = HotspotVoucher::where('generated_batch', $batch)->get();
    $ispName = IspSetting::get('company_name', 'NETSPEED WI-FI');

    return view('print.voucher-sheet', compact('vouchers', 'ispName', 'batch'));
})->name('print.vouchers');

Route::get('/print/voucher/{id}', function (int $id) {
    $voucher = HotspotVoucher::with('router')->findOrFail($id);
    $ispName = IspSetting::get('company_name', 'NETSPEED WI-FI');

    return view('print.voucher-single', compact('voucher', 'ispName'));
})->name('print.voucher.single');
