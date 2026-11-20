<?php

namespace App\Console\Commands;

use App\Services\Bandwidth\FupService;
use Illuminate\Console\Command;

class ResetMonthlyFupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fup:reset-cycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset siklus bulanan FUP dan pulihkan kecepatan maksimal seluruh pelanggan';

    /**
     * Execute the console command.
     */
    public function handle(FupService $fupService): int
    {
        $this->info('Memulai reset siklus bulanan FUP seluruh pelanggan...');

        $res = $fupService->resetMonthlyCycle();

        $this->info("Reset siklus selesai: {$res['restored']} dari {$res['total']} pelanggan ter-FUP berhasil dipulihkan ke kecepatan normal.");

        return self::SUCCESS;
    }
}
