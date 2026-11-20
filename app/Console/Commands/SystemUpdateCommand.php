<?php

namespace App\Console\Commands;

use App\Services\System\SystemUpdateService;
use Illuminate\Console\Command;

class SystemUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:update 
                            {--check : Hanya periksa pembaruan tanpa mengunduh} 
                            {--force : Timpa perubahan file lokal dengan git reset --hard} 
                            {--no-migrate : Lewati proses migrasi database} 
                            {--no-cache : Lewati proses optimasi cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa dan terapkan pembaruan sistem NetSpeed ISP Billing dari GitHub';

    /**
     * Execute the console command.
     */
    public function handle(SystemUpdateService $updateService): int
    {
        $this->info('====================================================');
        $this->info('  NetSpeed ISP Billing - GitHub System Updater');
        $this->info('====================================================');

        $versionInfo = $updateService->getCurrentVersionInfo();
        $this->line("Versi Saat Ini : <comment>{$versionInfo['commit_hash']}</comment> (Cabang: {$versionInfo['branch']})");
        $this->line("Pesan Commit   : {$versionInfo['commit_message']}");
        $this->line("Repository     : {$versionInfo['remote_url']}");
        $this->newLine();

        if ($this->option('check')) {
            $this->info('Sedang memeriksa pembaruan di GitHub...');
            $check = $updateService->checkForUpdates(forceFetch: true);

            if (! $check['success']) {
                $this->error('Gagal memeriksa pembaruan: '.($check['error'] ?? 'Terjadi kesalahan tidak diketahui.'));

                return self::FAILURE;
            }

            if ($check['is_up_to_date']) {
                $this->info('✅ Sistem Anda sudah menggunakan versi terbaru (Up to date).');
            } else {
                $this->warn("⚡ Ada {$check['commits_behind']} commit pembaruan tersedia di GitHub!");
                $this->line("Commit Terbaru : <comment>{$check['latest_remote_hash']}</comment> - {$check['latest_remote_message']}");

                if (! empty($check['incoming_commits'])) {
                    $this->newLine();
                    $this->line('Daftar Perubahan (Changelog):');
                    foreach ($check['incoming_commits'] as $c) {
                        $this->line(" - [<comment>{$c['hash']}</comment>] {$c['message']} (<info>{$c['author']}</info>)");
                    }
                }
            }

            return self::SUCCESS;
        }

        $this->warn('Memulai proses pembaruan dari GitHub...');
        $result = $updateService->performUpdate([
            'run_migrations' => ! $this->option('no-migrate'),
            'clear_cache' => ! $this->option('no-cache'),
            'upgrade_filament' => true,
            'force_reset' => (bool) $this->option('force'),
            'maintenance_mode' => true,
        ]);

        foreach ($result['logs'] as $log) {
            $statusPrefix = $log['success'] ? '<info>[OK]</info>' : '<error>[GAGAL]</error>';
            $this->line("{$statusPrefix} {$log['step']}");
            if (! empty($log['output'])) {
                $this->line('       '.str_replace("\n", "\n       ", trim($log['output'])));
            }
        }

        $this->newLine();
        if ($result['success']) {
            $this->info("✅ {$result['message']}");

            return self::SUCCESS;
        }

        $this->error("❌ {$result['message']}");

        return self::FAILURE;
    }
}
