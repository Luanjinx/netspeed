<?php

namespace App\Console\Commands;

use App\Models\HotspotVoucher;
use App\Models\Router;
use App\Services\Hotspot\VoucherService;
use App\Services\Router\MikrotikApiService;
use Illuminate\Console\Command;

class MikrotikSyncHotspotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync-hotspot {--router= : ID router tertentu} {--pull-profiles : Tarik profil hotspot dari MikroTik ke DB} {--sync-status : Cek sesi aktif dan tandai voucher terpakai} {--push-vouchers : Sinkronkan voucher DB ke MikroTik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan Profil Hotspot & Status Voucher antara MikroTik dan Database';

    /**
     * Execute the console command.
     */
    public function handle(VoucherService $voucherService, MikrotikApiService $api): int
    {
        $routerId = $this->option('router');
        $query = Router::where('is_active', true);
        if ($routerId) {
            $query->where('id', $routerId);
        }

        $routers = $query->get();

        if ($routers->isEmpty()) {
            $this->error('Tidak ada router aktif yang ditemukan.');

            return self::FAILURE;
        }

        $pullProfiles = $this->option('pull-profiles');
        $syncStatus = $this->option('sync-status');
        $pushVouchers = $this->option('push-vouchers');

        // If no specific flag, run all
        if (! $pullProfiles && ! $syncStatus && ! $pushVouchers) {
            $pullProfiles = true;
            $syncStatus = true;
            $pushVouchers = true;
        }

        foreach ($routers as $router) {
            $this->info("=== Memproses Hotspot Router: {$router->name} ({$router->host}:{$router->port}) ===");

            $test = $api->testConnection($router);
            if (! $test['success']) {
                $this->error("Gagal terhubung ke router: {$test['message']}");

                continue;
            }

            $this->line("Koneksi berhasil: {$test['message']}");

            // 1. Tarik Profil Hotspot dari MikroTik
            if ($pullProfiles) {
                $this->line('Menyinkronkan profil hotspot dari MikroTik (/ip/hotspot/user/profile)...');
                $profRes = $voucherService->pullProfilesFromRouter($router);
                $this->info("Profil selesai: {$profRes['total']} profil ditemukan di MikroTik -> {$profRes['imported']} paket baru, {$profRes['updated']} paket diperbarui.");
                foreach ($profRes['profiles'] as $p) {
                    $rate = $p['rate_limit'] ?? 'Default';
                    $this->line(" - Profil [{$p['name']}]: Speed: {$rate}, Shared: {$p['shared_users']}");
                }
            }

            // 2. Cek Sesi Aktif Hotspot
            if ($syncStatus) {
                $this->line('Mengecek sesi aktif hotspot (/ip/hotspot/active)...');
                $activeRes = $voucherService->syncVoucherStatus($router);
                $this->info("Sesi aktif selesai: {$activeRes['active_sessions_count']} perangkat sedang login. {$activeRes['vouchers_marked_used']} voucher baru ditandai 'used'.");
            }

            // 3. Push Voucher Unused ke MikroTik jika belum terdaftar
            if ($pushVouchers) {
                $this->line('Menyinkronkan voucher yang belum terdaftar di MikroTik...');
                $unusedVouchers = HotspotVoucher::where('router_id', $router->id)
                    ->where('status', 'unused')
                    ->get();

                $pushed = 0;
                foreach ($unusedVouchers as $voucher) {
                    $res = $voucherService->syncSingleVoucherToRouter($voucher);
                    if ($res['success'] ?? false) {
                        $pushed++;
                    }
                }
                $this->info("Push voucher selesai: {$pushed} / {$unusedVouchers->count()} voucher tersinkron ke MikroTik.");
            }
        }

        $this->newLine();
        $this->info('✓ Seluruh sinkronisasi Hotspot MikroTik selesai dengan sukses!');

        return self::SUCCESS;
    }
}
