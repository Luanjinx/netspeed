<?php

namespace App\Filament\Resources\HotspotVouchers\Pages;

use App\Filament\Resources\HotspotVouchers\HotspotVoucherResource;
use App\Services\Hotspot\VoucherService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditHotspotVoucher extends EditRecord
{
    protected static string $resource = HotspotVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function () {
                    $this->record->skip_router_deletion = true;
                    if ($this->record->router) {
                        $removed = app(VoucherService::class)->removeSingleVoucherFromRouter($this->record);
                        if (! $removed) {
                            Notification::make()
                                ->title('Peringatan: MikroTik Tidak Merespons')
                                ->body("Voucher [{$this->record->code}] dihapus dari web, namun router tidak merespons atau user sudah tidak ada di MikroTik.")
                                ->warning()
                                ->send();
                        }
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        $voucher = $this->record;
        $syncResult = app(VoucherService::class)->syncSingleVoucherToRouter($voucher);

        if ($syncResult['success'] ?? false) {
            Notification::make()
                ->title('Perubahan Voucher Tersinkron ke MikroTik')
                ->body("User [{$voucher->code}] di MikroTik berhasil diperbarui dengan profil [{$voucher->profile}].")
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
