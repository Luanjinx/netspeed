<?php

namespace App\Filament\Resources\AgentWithdrawals\Pages;

use App\Filament\Resources\AgentWithdrawals\AgentWithdrawalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgentWithdrawals extends ListRecords
{
    protected static string $resource = AgentWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
