<?php

namespace App\Filament\Resources\IspSettings\Pages;

use App\Filament\Resources\IspSettings\IspSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIspSetting extends EditRecord
{
    protected static string $resource = IspSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
