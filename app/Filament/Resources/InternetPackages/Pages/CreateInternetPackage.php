<?php

namespace App\Filament\Resources\InternetPackages\Pages;

use App\Filament\Resources\InternetPackages\InternetPackageResource;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInternetPackage extends CreateRecord
{
    protected static string $resource = InternetPackageResource::class;

    protected function afterCreate(): void
    {
        $package = $this->record;
        if (! empty($package->mikrotik_profile) && in_array($package->type, ['pppoe', 'hotspot'])) {
            $res = app(MikrotikApiService::class)->syncInternetPackageProfile($package);
            if ($res['success'] ?? false) {
                Notification::make()
                    ->title('Profil MikroTik Otomatis Dibuat')
                    ->body("Profil [{$package->mikrotik_profile}] ({$package->type}) berhasil dibuat di router MikroTik.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Pemberitahuan Profil MikroTik')
                    ->body($res['message'] ?? 'Tidak dapat terhubung ke router MikroTik saat ini.')
                    ->warning()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
