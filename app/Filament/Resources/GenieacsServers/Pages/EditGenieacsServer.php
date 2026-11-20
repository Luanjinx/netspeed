<?php

namespace App\Filament\Resources\GenieacsServers\Pages;

use App\Filament\Resources\GenieacsServers\GenieacsServerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGenieacsServer extends EditRecord
{
    protected static string $resource = GenieacsServerResource::class;

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
