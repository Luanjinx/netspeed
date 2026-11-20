<?php

namespace App\Filament\Resources\InternetPackages\Pages;

use App\Filament\Resources\InternetPackages\InternetPackageResource;
use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInternetPackages extends ListRecords
{
    protected static string $resource = InternetPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAllToMikrotik')
                ->label('Kirim Semua Profil ke MikroTik')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Kirim Seluruh Profil Paket ke MikroTik')
                ->modalDescription('Seluruh profil paket internet (PPPoE dan Hotspot) akan otomatis dibuat dan diperbarui di router MikroTik dengan limit kecepatan dan shared users yang sesuai.')
                ->requiresConfirmation()
                ->action(function (MikrotikApiService $service) {
                    $res = $service->syncAllInternetPackagesToRouters();
                    Notification::make()
                        ->title('Sinkronisasi Profil Paket Selesai')
                        ->body("Total Paket: {$res['total']} paket.\nBerhasil disinkronkan: {$res['success']} paket.\nGagal: {$res['failed']} paket.")
                        ->success()
                        ->send();
                }),

            Action::make('importProfiles')
                ->label('Tarik Profil dari MikroTik')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->schema([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->first()?->id)
                        ->required(),
                ])
                ->modalHeading('Tarik Profil dari MikroTik')
                ->modalDescription('Mengambil profil PPP dari router MikroTik dan menyimpannya sebagai Paket Internet PPPoE jika belum ada.')
                ->action(function (array $data, MikrotikApiService $service) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        Notification::make()->title('Router tidak ditemukan')->danger()->send();

                        return;
                    }

                    $res = $service->syncProfilesFromRouter($router);
                    Notification::make()
                        ->title('Tarik Profil MikroTik Berhasil')
                        ->body("Ditemukan {$res['total_profiles']} profil di MikroTik. {$res['synced_packages']} paket baru berhasil ditambahkan ke database.")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
