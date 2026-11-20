<?php

namespace App\Filament\Pages;

use App\Services\System\SystemUpdateService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class SystemUpdate extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'system-update';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pembaruan Sistem';

    protected static ?string $title = 'Pembaruan Sistem & GitHub Updater';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.system-update';

    /**
     * Current local git version info.
     *
     * @var array{
     *     has_git: bool,
     *     commit_hash: string,
     *     commit_full_hash: string,
     *     commit_message: string,
     *     commit_date: string,
     *     commit_author: string,
     *     branch: string,
     *     remote_url: string,
     *     repo_owner: string,
     *     repo_name: string,
     *     has_uncommitted_changes: bool
     * }
     */
    public array $versionInfo = [];

    /**
     * Remote update check result.
     *
     * @var array{
     *     success: bool,
     *     is_up_to_date: bool,
     *     commits_behind: int,
     *     latest_remote_hash: string,
     *     latest_remote_message: string,
     *     incoming_commits: array<int, array{hash: string, message: string, author: string, date: string, url: string}>,
     *     checked_at: string,
     *     error: ?string
     * }
     */
    public array $updateStatus = [];

    /**
     * Last update execution history.
     */
    public ?array $lastHistory = null;

    /**
     * Logs produced by the most recent update execution.
     *
     * @var array<int, array{step: string, command: string, output: string, success: bool}>
     */
    public array $executionLogs = [];

    public bool $isChecking = false;

    public bool $isUpdating = false;

    public ?string $lastErrorMessage = null;

    public ?string $lastSuccessMessage = null;

    public function mount(): void
    {
        $this->refreshVersionInfo();
        $this->checkUpdates(forceFetch: false);
        $this->loadLastHistory();
    }

    public function refreshVersionInfo(): void
    {
        $service = app(SystemUpdateService::class);
        $this->versionInfo = $service->getCurrentVersionInfo();
    }

    public function loadLastHistory(): void
    {
        $service = app(SystemUpdateService::class);
        $this->lastHistory = $service->getLastUpdateHistory();

        if (! empty($this->lastHistory['logs']) && empty($this->executionLogs)) {
            $this->executionLogs = $this->lastHistory['logs'];
        }
    }

    public function checkUpdates(bool $forceFetch = true): void
    {
        $this->isChecking = true;
        $this->lastErrorMessage = null;

        try {
            $service = app(SystemUpdateService::class);
            $this->updateStatus = $service->checkForUpdates($forceFetch);

            if (! $this->updateStatus['success']) {
                $this->lastErrorMessage = $this->updateStatus['error'] ?? 'Gagal memeriksa pembaruan di GitHub.';
                if ($forceFetch) {
                    Notification::make()
                        ->title('Gagal Memeriksa Pembaruan')
                        ->body($this->lastErrorMessage)
                        ->danger()
                        ->send();
                }
            } else {
                if ($forceFetch) {
                    if ($this->updateStatus['is_up_to_date']) {
                        Notification::make()
                            ->title('Sistem Sudah Terkini (Up to Date)')
                            ->body('Tidak ada commit baru di repository GitHub.')
                            ->success()
                            ->send();
                    } else {
                        $count = $this->updateStatus['commits_behind'];
                        Notification::make()
                            ->title("⚡ Ada {$count} Pembaruan Tersedia!")
                            ->body("Commit terbaru: {$this->updateStatus['latest_remote_hash']} - {$this->updateStatus['latest_remote_message']}")
                            ->warning()
                            ->duration(8000)
                            ->send();
                    }
                }
            }
        } finally {
            $this->isChecking = false;
        }
    }

    public function executeSystemUpdate(array $data = []): void
    {
        $this->isUpdating = true;
        $this->lastErrorMessage = null;
        $this->lastSuccessMessage = null;

        try {
            $service = app(SystemUpdateService::class);
            $result = $service->performUpdate([
                'run_migrations' => (bool) ($data['run_migrations'] ?? true),
                'clear_cache' => (bool) ($data['clear_cache'] ?? true),
                'upgrade_filament' => true,
                'force_reset' => (bool) ($data['force_reset'] ?? false),
                'maintenance_mode' => (bool) ($data['maintenance_mode'] ?? true),
            ]);

            $this->executionLogs = $result['logs'];
            $this->refreshVersionInfo();
            $this->loadLastHistory();

            if ($result['success']) {
                $this->lastSuccessMessage = $result['message'];
                $this->updateStatus['is_up_to_date'] = true;
                $this->updateStatus['commits_behind'] = 0;
                $this->updateStatus['incoming_commits'] = [];

                Notification::make()
                    ->title('✅ Pembaruan Sistem Berhasil!')
                    ->body($result['message'])
                    ->success()
                    ->duration(10000)
                    ->send();
            } else {
                $this->lastErrorMessage = $result['message'];

                Notification::make()
                    ->title('Pembaruan Mengalami Kendala')
                    ->body($result['message'])
                    ->danger()
                    ->duration(12000)
                    ->send();
            }
        } finally {
            $this->isUpdating = false;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkUpdates')
                ->label('Periksa GitHub')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->checkUpdates(forceFetch: true)),

            Action::make('performUpdate')
                ->label('Perbarui Sekarang')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembaruan Sistem dari GitHub')
                ->modalDescription('Sistem akan menarik kode terbaru dari GitHub repository, menjalankan migrasi database, dan membersihkan seluruh cache aplikasi.')
                ->modalSubmitActionLabel('Mulai Pembaruan Sekarang')
                ->form([
                    Toggle::make('run_migrations')
                        ->label('Jalankan Migrasi Database')
                        ->helperText('Menjalankan "php artisan migrate --force" jika terdapat perubahan skema database')
                        ->default(true),

                    Toggle::make('clear_cache')
                        ->label('Bersihkan & Optimasi Cache Sistem')
                        ->helperText('Menjalankan "php artisan optimize:clear" dan "php artisan optimize"')
                        ->default(true),

                    Toggle::make('maintenance_mode')
                        ->label('Mode Pemeliharaan Sementara (Maintenance Mode)')
                        ->helperText('Mengaktifkan mode "down" selama proses update dan kembali "up" secara otomatis setelah selesai')
                        ->default(true),

                    Toggle::make('force_reset')
                        ->label('Timpa Perubahan File Lokal (Force Reset)')
                        ->helperText('Gunakan opsi ini jika terdapat konflik file yang menghalangi git pull normal (akan menjalankan git reset --hard origin/main)')
                        ->default(false),
                ])
                ->action(fn (array $data) => $this->executeSystemUpdate($data)),
        ];
    }
}
