<?php

namespace App\Filament\Resources\PackageRequests;

use App\Filament\Resources\PackageRequests\Pages\EditPackageRequest;
use App\Filament\Resources\PackageRequests\Pages\ListPackageRequests;
use App\Filament\Resources\PackageRequests\Schemas\PackageRequestForm;
use App\Filament\Resources\PackageRequests\Tables\PackageRequestsTable;
use App\Models\PackageRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use UnitEnum;

class PackageRequestResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $model = PackageRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Pelanggan';

    protected static ?string $navigationLabel = 'Persetujuan Paket';

    protected static ?string $modelLabel = 'Pengajuan Paket';

    protected static ?string $pluralModelLabel = 'Persetujuan Daftar & Upgrade';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        if (! DatabaseSchema::hasTable('package_requests')) {
            return null;
        }

        $pendingCount = PackageRequest::where('status', PackageRequest::STATUS_PENDING)->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return PackageRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackageRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackageRequests::route('/'),
            'edit' => EditPackageRequest::route('/{record}/edit'),
        ];
    }
}
