<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Router;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverviewWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $data = Cache::remember('dashboard_stats_data', 30, function (): array {
            $activeCustomers = Customer::where('status', 'active')->count();
            $isolatedCustomers = Customer::where('status', 'isolated')->count();

            $currentPeriod = date('Y-m');
            $unpaidInvoices = Invoice::where('status', 'unpaid')->where('period', $currentPeriod)->sum('total_amount');
            $paidInvoices = Invoice::where('status', 'paid')->where('period', $currentPeriod)->sum('total_amount');

            $totalRouters = Router::count();
            $onlineRouters = Router::where('status', 'online')->count();

            return compact('activeCustomers', 'isolatedCustomers', 'unpaidInvoices', 'paidInvoices', 'totalRouters', 'onlineRouters');
        });

        $activeCustomers = $data['activeCustomers'];
        $isolatedCustomers = $data['isolatedCustomers'];
        $unpaidInvoices = $data['unpaidInvoices'];
        $paidInvoices = $data['paidInvoices'];
        $totalRouters = $data['totalRouters'];
        $onlineRouters = $data['onlineRouters'];

        return [
            Stat::make('Pelanggan Aktif', $activeCustomers)
                ->description("{$isolatedCustomers} pelanggan di-isolir")
                ->descriptionIcon($isolatedCustomers > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->chart([max(1, $activeCustomers - 4), max(1, $activeCustomers - 2), max(1, $activeCustomers - 1), $activeCustomers])
                ->color($isolatedCustomers > 0 ? 'warning' : 'success'),

            Stat::make('Tagihan Berjalan (Unpaid)', 'Rp '.number_format($unpaidInvoices, 0, ',', '.'))
                ->description('Periode: '.date('F Y'))
                ->descriptionIcon('heroicon-m-clock')
                ->chart([$unpaidInvoices * 0.9, $unpaidInvoices * 0.95, $unpaidInvoices])
                ->color('danger'),

            Stat::make('Pendapatan Bulan Ini', 'Rp '.number_format($paidInvoices, 0, ',', '.'))
                ->description('Total pembayaran lunas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([$paidInvoices * 0.4, $paidInvoices * 0.6, $paidInvoices * 0.8, $paidInvoices])
                ->color('success'),

            Stat::make('Status Router MikroTik', "{$onlineRouters} / {$totalRouters} Online")
                ->description($totalRouters === $onlineRouters && $totalRouters > 0 ? 'Seluruh router normal' : 'Ada router offline')
                ->descriptionIcon('heroicon-m-server-stack')
                ->chart([$onlineRouters, $onlineRouters, $totalRouters])
                ->color($totalRouters === $onlineRouters && $totalRouters > 0 ? 'success' : 'info'),
        ];
    }
}
