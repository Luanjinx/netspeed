<?php

namespace App\Filament\Resources\Promos\Pages;

use App\Filament\Resources\Promos\PromoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPromo extends EditRecord
{
    protected static string $resource = PromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['image_type'] ?? 'upload') === 'url') {
            $data['image'] = trim($data['image_url'] ?? '');
        } else {
            $image = $data['image_upload'] ?? null;
            if (is_array($image)) {
                $image = reset($image);
            }
            $data['image'] = $image ?: ($this->record->image ?? null);
        }
        unset($data['image_type'], $data['image_url'], $data['image_upload']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
