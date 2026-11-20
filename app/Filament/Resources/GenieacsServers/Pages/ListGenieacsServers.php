<?php

namespace App\Filament\Resources\GenieacsServers\Pages;

use App\Filament\Resources\GenieacsServers\GenieacsServerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGenieacsServers extends ListRecords
{
    protected static string $resource = GenieacsServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
