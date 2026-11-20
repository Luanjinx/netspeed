<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importPppoe')
                ->label('Tarik PPPoE dari MikroTik')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->schema([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->first()?->id)
                        ->required(),
                ])
                ->modalHeading('Tarik Data PPPoE dari MikroTik')
                ->modalDescription('Sistem akan mengambil seluruh user PPPoE Secret dari router MikroTik dan menyinkronkan profil paket secara otomatis ke dalam data pelanggan.')
                ->action(function (array $data, MikrotikApiService $service) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        Notification::make()->title('Router tidak ditemukan')->danger()->send();

                        return;
                    }

                    $res = $service->importPppSecretsToCustomers($router);
                    Notification::make()
                        ->title('Sinkronisasi PPPoE Berhasil')
                        ->body("Total dari MikroTik: {$res['total']} user.\nImport Baru: {$res['imported']} pelanggan.\nUpdate: {$res['updated']} pelanggan.")
                        ->success()
                        ->send();
                }),

            Action::make('pushAllPppoe')
                ->label('Kirim Semua ke MikroTik')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->schema([
                    Select::make('router_id')
                        ->label('Pilih Router MikroTik')
                        ->options(Router::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => Router::where('is_active', true)->first()?->id)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Kirim Seluruh Pelanggan PPPoE ke MikroTik?')
                ->modalDescription('Semua pelanggan PPPoE di database pada router ini akan dibuatkan/diperbarui akun PPPoE Secret-nya di MikroTik.')
                ->action(function (array $data, MikrotikApiService $service) {
                    $router = Router::find($data['router_id']);
                    if (! $router) {
                        Notification::make()->title('Router tidak ditemukan')->danger()->send();

                        return;
                    }

                    $res = $service->syncAllCustomersToMikrotik($router);
                    Notification::make()
                        ->title('Push ke MikroTik Selesai')
                        ->body("Diproses: {$res['total']} pelanggan.\nBerhasil: {$res['success']}\nGagal: {$res['failed']}")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
