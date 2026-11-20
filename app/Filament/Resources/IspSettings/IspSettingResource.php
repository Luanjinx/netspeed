<?php

namespace App\Filament\Resources\IspSettings;

use App\Filament\Resources\IspSettings\Pages\CreateIspSetting;
use App\Filament\Resources\IspSettings\Pages\EditIspSetting;
use App\Filament\Resources\IspSettings\Pages\ListIspSettings;
use App\Filament\Resources\IspSettings\Schemas\IspSettingForm;
use App\Filament\Resources\IspSettings\Tables\IspSettingsTable;
use App\Models\IspSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class IspSettingResource extends Resource
{
    protected static ?string $model = IspSetting::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Pengaturan ISP & QRIS';

    protected static ?string $modelLabel = 'Pengaturan ISP';

    protected static ?string $pluralModelLabel = 'Pengaturan ISP';

    public static function form(Schema $schema): Schema
    {
        return IspSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IspSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIspSettings::route('/'),
            'create' => CreateIspSetting::route('/create'),
            'edit' => EditIspSetting::route('/{record}/edit'),
        ];
    }
}
