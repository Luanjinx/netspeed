<?php

namespace App\Filament\Resources\Odps\Pages;

use App\Filament\Resources\Odps\OdpResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOdp extends CreateRecord
{
    protected static string $resource = OdpResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
