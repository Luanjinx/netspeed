<?php

namespace App\Filament\Resources\InternetPackages;

use App\Filament\Resources\InternetPackages\Pages\CreateInternetPackage;
use App\Filament\Resources\InternetPackages\Pages\EditInternetPackage;
use App\Filament\Resources\InternetPackages\Pages\ListInternetPackages;
use App\Filament\Resources\InternetPackages\Schemas\InternetPackageForm;
use App\Filament\Resources\InternetPackages\Tables\InternetPackagesTable;
use App\Models\InternetPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class InternetPackageResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $model = InternetPackage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wifi';

    protected static string|UnitEnum|null $navigationGroup = 'Data Pelanggan';

    protected static ?string $navigationLabel = 'Paket Internet';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Paket Internet';

    protected static ?string $pluralModelLabel = 'Paket Internet';

    public static function form(Schema $schema): Schema
    {
        return InternetPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternetPackagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternetPackages::route('/'),
            'create' => CreateInternetPackage::route('/create'),
            'edit' => EditInternetPackage::route('/{record}/edit'),
        ];
    }
}
