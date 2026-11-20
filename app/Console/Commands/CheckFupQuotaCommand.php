<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\Bandwidth\FupService;
use Illuminate\Console\Command;

class CheckFupQuotaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fup:check {router? : ID router spesifik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa pemakaian kuota pelanggan PPPoE dan terapkan FUP jika kuota bulanan terlampaui';

    /**
     * Execute the console command.
     */
    public function handle(FupService $fupService): int
    {
        $routerId = $this->argument('router');

        $routers = $routerId
            ? Router::where('id', $routerId)->orWhere('name', $routerId)->get()
            : Router::where('is_active', true)->get();

        if ($routers->isEmpty()) {
            $this->warn('Tidak ada router aktif yang ditemukan.');

            return self::SUCCESS;
        }

        $this->info("Memulai pemeriksaan FUP pada {$routers->count()} router...");

        foreach ($routers as $router) {
            $this->line("Memeriksa router: {$router->name} ({$router->host})...");
            $result = $fupService->checkRouterCustomersFup($router);

            if ($result['success'] ?? false) {
                $this->info("Router {$router->name}: {$result['checked']} pelanggan diperiksa. {$result['fup_applied']} FUP baru diterapkan. {$result['already_fup']} sedang FUP. {$result['normal']} normal.");
            } else {
                $this->error("Router {$router->name}: ".($result['message'] ?? 'Gagal'));
            }
        }

        $this->info('Pemeriksaan FUP selesai.');

        return self::SUCCESS;
    }
}
