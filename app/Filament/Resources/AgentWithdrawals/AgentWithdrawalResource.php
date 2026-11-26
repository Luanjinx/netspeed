<?php

namespace App\Filament\Resources\AgentWithdrawals;

use App\Filament\Resources\AgentWithdrawals\Pages\ListAgentWithdrawals;
use App\Filament\Resources\AgentWithdrawals\Schemas\AgentWithdrawalForm;
use App\Filament\Resources\AgentWithdrawals\Tables\AgentWithdrawalsTable;
use App\Models\AgentWithdrawal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use UnitEnum;

class AgentWithdrawalResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $model = AgentWithdrawal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Tarik Saldo Agen';

    protected static ?string $modelLabel = 'Penarikan Saldo';

    protected static ?string $pluralModelLabel = 'Penarikan Saldo Agen';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        if (! DatabaseSchema::hasTable('agent_withdrawals')) {
            return null;
        }

        $pending = AgentWithdrawal::where('status', AgentWithdrawal::STATUS_PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return AgentWithdrawalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgentWithdrawalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgentWithdrawals::route('/'),
        ];
    }
}
