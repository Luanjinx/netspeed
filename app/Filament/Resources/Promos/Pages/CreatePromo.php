<?php

namespace App\Filament\Resources\Promos\Pages;

use App\Filament\Resources\Promos\PromoResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePromo extends CreateRecord
{
    protected static string $resource = PromoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['image_type'] ?? 'upload') === 'url') {
            $data['image'] = trim($data['image_url'] ?? '');
        } else {
            $image = $data['image_upload'] ?? ($data['image'] ?? null);
            if (is_array($image)) {
                $image = reset($image);
            }
            $data['image'] = $image ?: null;
        }
        unset($data['image_type'], $data['image_url'], $data['image_upload']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
