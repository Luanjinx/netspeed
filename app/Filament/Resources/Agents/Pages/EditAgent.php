<?php

namespace App\Filament\Resources\Agents\Pages;

use App\Filament\Resources\Agents\AgentResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAgent extends EditRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user) {
            $data['email'] = $this->record->user->email;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $agent = $this->record;

        if ($agent->user) {
            $update = [
                'name' => $agent->name,
                'phone' => $agent->phone,
                'is_active' => $agent->is_active,
            ];
            if (! empty($data['email'])) {
                $update['email'] = $data['email'];
            }
            if (! empty($data['password'])) {
                $update['password'] = bcrypt($data['password']);
            }
            $agent->user->update($update);
        } elseif (! empty($data['email']) && ! empty($data['password'])) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $agent->name,
                    'password' => bcrypt($data['password']),
                    'role' => 'agent',
                    'phone' => $agent->phone,
                    'is_active' => $agent->is_active,
                ]
            );

            if ($user->role !== 'agent') {
                $user->update(['role' => 'agent']);
            }

            $agent->update(['user_id' => $user->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
