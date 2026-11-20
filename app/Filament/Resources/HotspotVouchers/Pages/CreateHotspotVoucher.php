<?php

namespace App\Filament\Resources\HotspotVouchers\Pages;

use App\Filament\Resources\HotspotVouchers\HotspotVoucherResource;
use App\Models\Router;
use App\Services\Hotspot\VoucherService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateHotspotVoucher extends CreateRecord
{
    protected static string $resource = HotspotVoucherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['profile'])) {
            $router = ! empty($data['router_id']) ? Router::find($data['router_id']) : null;
            $meta = app(VoucherService::class)->extractProfileMetadata($data['profile'], $router);

            // Automatically ensure validity follows the MikroTik profile
            if (empty($data['validity']) || $data['validity'] === '1 Hari') {
                $data['validity'] = $meta['validity'];
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $voucher = $this->record;
        $syncResult = app(VoucherService::class)->syncSingleVoucherToRouter($voucher);

        if ($syncResult['success'] ?? false) {
            Notification::make()
                ->title('Voucher Hotspot Tersinkron ke MikroTik')
                ->body("Kode [{$voucher->code}] berhasil ditambahkan ke user hotspot MikroTik dengan profil [{$voucher->profile}].")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Peringatan: Gagal Sinkron ke MikroTik')
                ->body($syncResult['message'] ?? 'Tidak dapat terhubung ke router MikroTik saat ini.')
                ->warning()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
