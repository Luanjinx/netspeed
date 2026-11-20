<?php

namespace Tests\Feature;

use App\Filament\Widgets\NetworkHealthWidget;
use App\Models\Customer;
use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_router_system_resources_and_interface_traffic(): void
    {
        $router = Router::create([
            'name' => 'MikroTik Core - RB4011',
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);

        $service = app(MikrotikApiService::class);

        $resources = $service->getSystemResources($router);
        $this->assertTrue($resources['success']);
        $this->assertArrayHasKey('cpu_load', $resources);
        $this->assertArrayHasKey('uptime', $resources);
        $this->assertArrayHasKey('board_name', $resources);

        $interfaces = $service->getInterfacesWithTraffic($router);
        $this->assertTrue($interfaces['success']);
        $this->assertNotEmpty($interfaces['interfaces']);
    }

    public function test_customer_live_traffic_diagnostics(): void
    {
        $router = Router::create([
            'name' => 'MikroTik Core - RB4011',
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-001',
            'name' => 'Budi Santoso',
            'phone' => '0812345678',
            'router_id' => $router->id,
            'pppoe_username' => 'budi_santoso',
            'pppoe_password' => 'secret123',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        $service = app(MikrotikApiService::class);

        $traffic = $service->getCustomerLiveTraffic($customer);
        $this->assertTrue($traffic['success']);
        $this->assertEquals($customer->pppoe_username, $traffic['username']);
    }

    public function test_network_health_widget_data(): void
    {
        Router::create([
            'name' => 'MikroTik Core - RB4011',
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);

        $widget = new NetworkHealthWidget;
        $data = $widget->getViewData();

        $this->assertArrayHasKey('total_routers', $data);
        $this->assertArrayHasKey('potential_outages', $data);
        $this->assertArrayHasKey('active_customers', $data);
        $this->assertArrayHasKey('healthy_rate', $data);
    }

    public function test_formatting_helpers(): void
    {
        $service = app(MikrotikApiService::class);

        $this->assertEquals('15.50 Mbps', $service->formatBitsPerSecond(15500000));
        $this->assertEquals('1.50 Gbps', $service->formatBitsPerSecond(1500000000));
        $this->assertEquals('500.0 kbps', $service->formatBitsPerSecond(500000));

        $this->assertEquals('2.00 GB', $service->formatBytes(2147483648));
        $this->assertEquals('512.00 MB', $service->formatBytes(536870912));
    }

    public function test_network_health_widget_renders_cleanly(): void
    {
        $widget = new NetworkHealthWidget;
        $html = view('filament.widgets.network-health-widget', $widget->getViewData())->render();

        $this->assertStringContainsString('Pusat Diagnostik', $html);
        $this->assertStringContainsString('noc-grid-3', $html);
        $this->assertStringContainsString('noc-subcard', $html);
    }
}
