<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\GenieacsServer;
use App\Models\Odp;
use App\Models\Router;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class NetworkHealthWidget extends Widget
{
    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected string $view = 'filament.widgets.network-health-widget';

    public function getViewData(): array
    {
        return Cache::remember('dashboard_network_health_data', 30, function (): array {
            // 1. Router status
            $totalRouters = Router::where('is_active', true)->count();
            $onlineRouters = Router::where('is_active', true)->where('status', 'online')->count();
            $offlineRouters = $totalRouters - $onlineRouters;

            // 2. GenieACS status
            $genieacs = GenieacsServer::where('is_active', true)->first();
            $genieacsOnline = $genieacs && $genieacs->status === 'online';

            // 3. Outage Detection per ODP (Fiber Cut / Power Outage)
            $potentialOutages = [];
            $odps = Odp::withCount([
                'customers',
                'customers as offline_cust_count' => fn ($q) => $q->whereIn('status', ['isolated', 'pending']),
            ])->get();

            foreach ($odps as $odp) {
                $custCount = $odp->customers_count;
                if ($custCount === 0) {
                    continue;
                }

                $offlineCust = $odp->offline_cust_count;

                // If more than 50% of customers on an ODP are offline/isolated
                if ($offlineCust > 0 && ($offlineCust / $custCount) >= 0.5) {
                    $potentialOutages[] = [
                        'odp_id' => $odp->id,
                        'odp_name' => $odp->name,
                        'code' => $odp->code,
                        'address' => $odp->address ?: 'Lokasi ODP',
                        'total_cust' => $custCount,
                        'offline_cust' => $offlineCust,
                        'percentage' => round(($offlineCust / $custCount) * 100),
                    ];
                }
            }

            // 4. Customer Health Summary
            $activeCustomers = Customer::where('status', 'active')->count();
            $isolatedCustomers = Customer::where('status', 'isolated')->count();
            $totalCustomers = $activeCustomers + $isolatedCustomers;
            $healthyRate = $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 100;

            return [
                'total_routers' => $totalRouters,
                'online_routers' => $onlineRouters,
                'offline_routers' => $offlineRouters,
                'genieacs_online' => $genieacsOnline,
                'genieacs_name' => $genieacs?->name ?? 'Server TR-069',
                'potential_outages' => $potentialOutages,
                'active_customers' => $activeCustomers,
                'isolated_customers' => $isolatedCustomers,
                'healthy_rate' => $healthyRate,
            ];
        });
    }
}
