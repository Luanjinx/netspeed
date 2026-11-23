<?php

namespace App\Filament\Resources\GenieacsServers;

use App\Filament\Resources\GenieacsServers\Pages\CreateGenieacsServer;
use App\Filament\Resources\GenieacsServers\Pages\EditGenieacsServer;
use App\Filament\Resources\GenieacsServers\Pages\ListGenieacsServers;
use App\Filament\Resources\GenieacsServers\Schemas\GenieacsServerForm;
use App\Filament\Resources\GenieacsServers\Tables\GenieacsServersTable;
use App\Models\GenieacsServer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class GenieacsServerResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = GenieacsServer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Server GenieACS (ONT)';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Server GenieACS';

    protected static ?string $pluralModelLabel = 'Server GenieACS';

    public static function form(Schema $schema): Schema
    {
        return GenieacsServerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GenieacsServersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGenieacsServers::route('/'),
            'create' => CreateGenieacsServer::route('/create'),
            'edit' => EditGenieacsServer::route('/{record}/edit'),
        ];
    }
}
