<?php

namespace App\Filament\Resources\Agents\Pages;

use App\Filament\Resources\Agents\AgentResource;
use App\Models\Agent;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['agent_code'])) {
            $latestId = (int) Agent::max('id') + 1;
            $data['agent_code'] = 'AGT-'.sprintf('%03d', $latestId);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        if (! empty($data['email']) && ! empty($data['password'])) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $this->record->name,
                    'password' => bcrypt($data['password']),
                    'role' => 'agent',
                    'phone' => $this->record->phone,
                    'is_active' => $this->record->is_active,
                ]
            );

            if ($user->role !== 'agent') {
                $user->update(['role' => 'agent']);
            }

            $this->record->update(['user_id' => $user->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
