<?php

namespace App\Filament\Resources\PackageRequests\Pages;

use App\Filament\Resources\PackageRequests\PackageRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackageRequests extends ListRecords
{
    protected static string $resource = PackageRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
