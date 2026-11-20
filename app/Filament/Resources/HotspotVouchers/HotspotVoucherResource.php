<?php

namespace App\Filament\Resources\HotspotVouchers;

use App\Filament\Resources\HotspotVouchers\Pages\CreateHotspotVoucher;
use App\Filament\Resources\HotspotVouchers\Pages\EditHotspotVoucher;
use App\Filament\Resources\HotspotVouchers\Pages\ListHotspotVouchers;
use App\Filament\Resources\HotspotVouchers\Schemas\HotspotVoucherForm;
use App\Filament\Resources\HotspotVouchers\Tables\HotspotVouchersTable;
use App\Models\HotspotVoucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class HotspotVoucherResource extends Resource
{
    protected static ?string $model = HotspotVoucher::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Voucher Hotspot';

    protected static ?string $modelLabel = 'Voucher Hotspot';

    protected static ?string $pluralModelLabel = 'Voucher Hotspot';

    public static function form(Schema $schema): Schema
    {
        return HotspotVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HotspotVouchersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHotspotVouchers::route('/'),
            'create' => CreateHotspotVoucher::route('/create'),
            'edit' => EditHotspotVoucher::route('/{record}/edit'),
        ];
    }
}
