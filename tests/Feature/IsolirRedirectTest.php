<?php

namespace Tests\Feature;

use App\Filament\Pages\IsolirManager;
use App\Models\Customer;
use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IsolirRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_isolir_setup_script(): void
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
        $script = $service->generateIsolirSetupScript($router, [
            'gateway_ip' => '10.10.10.1',
            'pool_range' => '10.10.10.2-10.10.10.254',
            'pool_name' => 'pool-isolir',
            'ppp_profile_name' => 'ISOLIR',
        ]);

        $this->assertStringContainsString('/ip pool add name="pool-isolir"', $script);
        $this->assertStringContainsString('/interface bridge add name="bridge-isolir"', $script);
        $this->assertStringContainsString('/ip address add address=10.10.10.1/24', $script);
        $this->assertStringContainsString('/ip hotspot add name="hotspot-isolir"', $script);
        $this->assertStringContainsString('/ppp profile add name="ISOLIR"', $script);
        $this->assertStringNotContainsString('/ip hotspot user profile add name="ISOLIR"', $script);
        $this->assertStringContainsString('*whatsapp.com', $script);
    }

    public function test_get_isolir_html_content(): void
    {
        $service = app(MikrotikApiService::class);
        $html = $service->getIsolirHtmlContent([
            'company_name' => 'NetSpeed Provider',
            'company_phone' => '081234567890',
            'bank_account' => 'BCA 1234567890',
            'billing_url' => 'https://billing.isp.net',
        ]);

        $this->assertStringContainsString('NetSpeed Provider', $html);
        $this->assertStringContainsString('Layanan Internet Di-Isolir', $html);
        $this->assertStringContainsString('wa.me/6281234567890', $html);
        $this->assertStringContainsString('BCA 1234567890', $html);
        $this->assertStringContainsString('https://billing.isp.net', $html);
    }

    public function test_isolir_manager_page_mounts_and_generates_script(): void
    {
        $router = Router::create([
            'name' => 'MikroTik Core - RB4011',
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);

        Livewire::test(IsolirManager::class)
            ->assertSet('selectedRouterId', $router->id)
            ->assertSee('Setup Otomatis (1-Click API)')
            ->assertSee('Skrip Terminal RouterOS')
            ->assertSee('hotspot-isolir');
    }

    public function test_isolate_and_activate_customer_by_connection_type(): void
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

        // PPPoE Customer
        $pppoeCust = Customer::create([
            'customer_number' => 'CUST-PPP-01',
            'name' => 'Budi PPPoE',
            'phone' => '0812345678',
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'budi_ppp',
            'pppoe_password' => 'secret',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        // Hotspot Customer
        $hotspotCust = Customer::create([
            'customer_number' => 'CUST-HS-01',
            'name' => 'Joko Hotspot',
            'phone' => '0812345679',
            'router_id' => $router->id,
            'connection_type' => 'hotspot',
            'pppoe_username' => 'joko_hs',
            'pppoe_password' => 'secret',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        // Static IP Customer
        $staticCust = Customer::create([
            'customer_number' => 'CUST-STAT-01',
            'name' => 'Siti Static',
            'phone' => '0812345680',
            'router_id' => $router->id,
            'connection_type' => 'static',
            'ip_address' => '192.168.10.50',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        // Connect will fail cleanly since router 192.168.88.1 is offline, returning false without throwing exceptions
        $this->assertFalse($service->isolateCustomer($pppoeCust));
        $this->assertFalse($service->isolateCustomer($hotspotCust));
        $this->assertFalse($service->isolateCustomer($staticCust));

        $this->assertFalse($service->activateCustomer($pppoeCust));
        $this->assertFalse($service->activateCustomer($hotspotCust));
        $this->assertFalse($service->activateCustomer($staticCust));
    }
}
