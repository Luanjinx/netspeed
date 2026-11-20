<?php

namespace App\Filament\Resources\Routers;

use App\Filament\Resources\Routers\Pages\CreateRouter;
use App\Filament\Resources\Routers\Pages\EditRouter;
use App\Filament\Resources\Routers\Pages\ListRouters;
use App\Filament\Resources\Routers\Schemas\RouterForm;
use App\Filament\Resources\Routers\Tables\RoutersTable;
use App\Models\Router;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RouterResource extends Resource
{
    protected static ?string $model = Router::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Router MikroTik';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Router MikroTik';

    protected static ?string $pluralModelLabel = 'Router MikroTik';

    public static function form(Schema $schema): Schema
    {
        return RouterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoutersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRouters::route('/'),
            'create' => CreateRouter::route('/create'),
            'edit' => EditRouter::route('/{record}/edit'),
        ];
    }
}
