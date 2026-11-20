<?php

namespace App\Filament\Resources\GenieacsServers\Pages;

use App\Filament\Resources\GenieacsServers\GenieacsServerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGenieacsServer extends CreateRecord
{
    protected static string $resource = GenieacsServerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
