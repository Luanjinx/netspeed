<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Illuminate\Console\Command;

class MikrotikSyncPppoeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:sync-pppoe {--router= : ID router tertentu} {--pull : Hanya tarik dari MikroTik ke DB} {--push : Hanya kirim dari DB ke MikroTik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hubungkan dan sinkronkan data PPPoE Secrets & Profil antara MikroTik dan Database Billing';

    /**
     * Execute the console command.
     */
    public function handle(MikrotikApiService $service): int
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

        $onlyPull = $this->option('pull');
        $onlyPush = $this->option('push');

        foreach ($routers as $router) {
            $this->info("=== Memproses Router: {$router->name} ({$router->host}:{$router->port}) ===");

            $test = $service->testConnection($router);
            if (! $test['success']) {
                $this->error("Gagal terhubung ke router: {$test['message']}");

                continue;
            }

            $this->line("Koneksi berhasil: {$test['message']}");

            // 1. Sync Profiles
            $this->line('Menyinkronkan profil paket dari MikroTik...');
            $profRes = $service->syncProfilesFromRouter($router);
            $this->info("Profil selesai: {$profRes['total_profiles']} profil ditemukan. Isolir Profile: {$profRes['isolir_profile']}");

            // 2. Pull / Import Secrets from MikroTik into DB
            if (! $onlyPush) {
                $this->line('Menarik data PPPoE Secret dari MikroTik...');
                $importRes = $service->importPppSecretsToCustomers($router);
                $this->info("Import selesai: {$importRes['total']} user MikroTik -> {$importRes['imported']} pelanggan baru, {$importRes['updated']} diperbarui.");
            }

            // 3. Push / Sync DB Customers into MikroTik
            if (! $onlyPull) {
                $this->line('Mengirim pelanggan database ke MikroTik...');
                $pushRes = $service->syncAllCustomersToMikrotik($router);
                $this->info("Push selesai: {$pushRes['total']} pelanggan -> {$pushRes['success']} berhasil, {$pushRes['failed']} gagal.");
            }
        }

        $this->newLine();
        $this->info('✓ Seluruh sinkronisasi PPPoE MikroTik selesai dengan sukses!');

        return self::SUCCESS;
    }
}
