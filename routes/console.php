<?php

use App\Models\Invoice;
use App\Services\Billing\BillingService;
use App\Services\Notification\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Command: php artisan isp:auto-isolir
 * Automatically isolate customers who have overdue unpaid invoices.
 */
Artisan::command('isp:auto-isolir', function (BillingService $billing) {
    $this->info('Menjalankan proses auto-isolir pelanggan menunggak...');
    $count = $billing->processAutoIsolir();
    $this->info("Berhasil! {$count} pelanggan telah di-isolir di MikroTik.");
})->purpose('Proses auto isolir pelanggan yang melewati tanggal jatuh tempo');

/**
 * Command: php artisan isp:generate-invoices {period?}
 * Generate monthly invoices in batch.
 */
Artisan::command('isp:generate-invoices {period?}', function (BillingService $billing, $period = null) {
    $period = $period ?: date('Y-m');
    $this->info("Membuat tagihan bulanan untuk periode {$period}...");
    $count = $billing->generateMonthlyInvoices($period);
    $this->info("Berhasil! {$count} tagihan baru telah dibuat.");
})->purpose('Generate tagihan bulanan pelanggan secara batch');

/**
 * Command: php artisan isp:send-reminders
 * Send WhatsApp payment reminders for invoices due in 3 days or due today.
 */
Artisan::command('isp:send-reminders', function (WhatsAppService $waService) {
    $today = Carbon::today()->toDateString();
    $in3Days = Carbon::today()->addDays(3)->toDateString();

    $this->info('Mengirim pengingat jatuh tempo WhatsApp...');

    // H-3 reminders
    $h3Invoices = Invoice::where('status', 'unpaid')->where('due_date', $in3Days)->get();
    $h3Count = 0;
    foreach ($h3Invoices as $inv) {
        $waService->sendDueReminder($inv, 3);
        $h3Count++;
    }

    // H-0 reminders (due today)
    $h0Invoices = Invoice::where('status', 'unpaid')->where('due_date', $today)->get();
    $h0Count = 0;
    foreach ($h0Invoices as $inv) {
        $waService->sendDueReminder($inv, 0);
        $h0Count++;
    }

    $this->info("Pengingat terkirim: {$h3Count} tagihan H-3, {$h0Count} tagihan H-0 (Jatuh tempo hari ini).");
})->purpose('Kirim pengingat jatuh tempo tagihan via WhatsApp');

// Recurring cron tasks (STB / VPS / Shared Hosting Cron)
Schedule::command('isp:auto-isolir')->dailyAt('00:05');
Schedule::command('isp:send-reminders')->dailyAt('09:00');
Schedule::command('isp:generate-invoices')->monthlyOn(1, '00:01');
Schedule::command('fup:check')->everyThirtyMinutes();
Schedule::command('fup:reset-cycle')->monthlyOn(1, '00:02');
