<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Filament\Resources\AdminUsers\Schemas\AdminUserForm;
use App\Filament\Resources\AdminUsers\Tables\AdminUsersTable;
use App\Models\AdminUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AdminUserResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $model = AdminUser::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Kelola Akun Admin';

    protected static ?string $modelLabel = 'Administrator';

    protected static ?string $pluralModelLabel = 'Administrator';

    protected static ?int $navigationSort = 98;

    public static function form(Schema $schema): Schema
    {
        return AdminUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminUsers::route('/'),
            'create' => CreateAdminUser::route('/create'),
            'edit' => EditAdminUser::route('/{record}/edit'),
        ];
    }
}
