<?php

namespace App\Filament\Resources\PackageRequests\Pages;

use App\Filament\Resources\PackageRequests\PackageRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPackageRequest extends EditRecord
{
    protected static string $resource = PackageRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
