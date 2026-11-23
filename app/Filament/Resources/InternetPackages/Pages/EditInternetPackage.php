<?php

namespace App\Filament\Resources\InternetPackages\Pages;

use App\Filament\Resources\InternetPackages\InternetPackageResource;
use App\Services\Router\MikrotikApiService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInternetPackage extends EditRecord
{
    protected static string $resource = InternetPackageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['image_type'] ?? 'upload') === 'url') {
            $data['image'] = trim($data['image_url'] ?? '');
        } else {
            $data['image'] = $data['image_upload'] ?? $this->record->image;
        }
        unset($data['image_type'], $data['image_url'], $data['image_upload']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncToMikrotik')
                ->label('Kirim ke MikroTik')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn () => ! empty($this->record->mikrotik_profile) && in_array($this->record->type, ['pppoe', 'hotspot']))
                ->action(function (MikrotikApiService $api) {
                    $res = $api->syncInternetPackageProfile($this->record);
                    if ($res['success'] ?? false) {
                        Notification::make()
                            ->title('Sukses Sinkron ke MikroTik')
                            ->body($res['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Sinkron ke MikroTik')
                            ->body($res['message'] ?? 'Tidak dapat terhubung ke router MikroTik.')
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make()
                ->successNotificationTitle('Paket & Profil MikroTik Berhasil Dihapus'),
        ];
    }

    protected function afterSave(): void
    {
        $package = $this->record;
        if (! empty($package->mikrotik_profile) && in_array($package->type, ['pppoe', 'hotspot'])) {
            $res = app(MikrotikApiService::class)->syncInternetPackageProfile($package);
            if ($res['success'] ?? false) {
                Notification::make()
                    ->title('Profil MikroTik Diperbarui')
                    ->body("Profil [{$package->mikrotik_profile}] ({$package->type}) berhasil disinkronkan ke router MikroTik.")
                    ->success()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
