<?php

namespace App\Filament\Resources\IspSettings\Pages;

use App\Filament\Pages\AppSettings;
use App\Filament\Resources\IspSettings\IspSettingResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIspSettings extends ListRecords
{
    protected static string $resource = IspSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openUnifiedSettings')
                ->label('⚙️ Buka Pengaturan Terpadu (API & Sistem)')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('primary')
                ->url(AppSettings::getUrl()),

            CreateAction::make()->label('Tambah Parameter Manual'),
        ];
    }
}
