<?php

namespace App\Filament\Widgets;

use App\Models\AgentTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AgentStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'agent';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $agent = $user?->agent;

        if (! $agent) {
            return [
                Stat::make('Status Kemitraan Agen', 'Belum Terhubung')
                    ->description('Hubungi Administrator untuk menautkan akun login Anda dengan profil loket agen.')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        $balance = (float) $agent->balance;
        $startOfMonth = now()->startOfMonth();

        $monthlyCommission = (float) AgentTransaction::where('agent_id', $agent->id)
            ->where('type', 'pppoe_payment')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('fee');

        $monthlySuccessCount = AgentTransaction::where('agent_id', $agent->id)
            ->where('type', 'pppoe_payment')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $commissionPerTrx = (float) $agent->commission_per_transaction;

        return [
            Stat::make('Saldo Deposit Loket', $agent->formatted_balance)
                ->description($balance > 0 ? 'Sisa deposit aktif untuk melayani pembayaran' : 'Saldo deposit habis, segera setor ke admin')
                ->descriptionIcon($balance > 0 ? 'heroicon-m-wallet' : 'heroicon-m-exclamation-circle')
                ->chart([max(0, $balance - 50000), max(0, $balance - 20000), $balance])
                ->color($balance > 100000 ? 'success' : ($balance > 0 ? 'warning' : 'danger')),

            Stat::make('Total Komisi Bulan Ini', 'Rp '.number_format($monthlyCommission, 0, ',', '.'))
                ->description('Keuntungan periode '.now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([$monthlyCommission * 0.4, $monthlyCommission * 0.7, $monthlyCommission])
                ->color('success'),

            Stat::make('Transaksi PPPoE Lunas', "{$monthlySuccessCount} Pelanggan")
                ->description('Tagihan terproses & aktif otomatis')
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([max(0, $monthlySuccessCount - 2), max(0, $monthlySuccessCount - 1), $monthlySuccessCount])
                ->color('primary'),

            Stat::make('Komisi per Tagihan', 'Rp '.number_format($commissionPerTrx, 0, ',', '.'))
                ->description('Potongan keuntungan tunai per invoice')
                ->descriptionIcon('heroicon-m-gift')
                ->color('info'),
        ];
    }
}
