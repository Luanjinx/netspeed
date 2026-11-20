<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected static ?string $title = 'Terbitkan Surat Tugas (SPK)';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
