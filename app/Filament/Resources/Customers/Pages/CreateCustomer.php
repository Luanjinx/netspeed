<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function afterCreate(): void
    {
        $customer = $this->record;
        if ($customer->connection_type === 'pppoe' && $customer->router_id && ! empty($customer->pppoe_username)) {
            $service = app(MikrotikApiService::class);
            $res = $service->syncCustomerToMikrotik($customer);
            if ($res['success']) {
                Notification::make()
                    ->title('PPPoE MikroTik Terhubung')
                    ->body($res['message'])
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Peringatan MikroTik')
                    ->body($res['message'])
                    ->warning()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
