<?php

namespace App\Filament\Resources\IspSettings\Pages;

use App\Filament\Resources\IspSettings\IspSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIspSetting extends CreateRecord
{
    protected static string $resource = IspSettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
