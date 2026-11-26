<?php

namespace App\Filament\Resources\AgentWithdrawals\Pages;

use App\Filament\Resources\AgentWithdrawals\AgentWithdrawalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAgentWithdrawal extends EditRecord
{
    protected static string $resource = AgentWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
