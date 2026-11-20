<?php

namespace Tests\Feature;

use App\Filament\Pages\AppSettings;
use App\Filament\Pages\PppoeManager;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Router;
use App\Models\User;
use App\Services\Router\MikrotikApiService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PppoeLocalAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_safe_pppoe_local_address_detects_and_avoids_hotspot_conflict(): void
    {
        $router = Router::create([
            'name' => 'Router Test Conflict',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $mockApi = $this->getMockBuilder(MikrotikApiService::class)
            ->onlyMethods(['connect', 'comm'])
            ->getMock();

        $mockApi->method('connect')->willReturn(true);

        // Suppose IspSetting is configured with 192.168.3.1 (which would conflict with Hotspot on 192.168.3.2)
        IspSetting::set('pppoe_local_address', '192.168.3.1');

        $mockApi->method('comm')->willReturnCallback(function ($cmd) {
            if ($cmd === '/ip/hotspot/profile/print') {
                return [
                    '!re' => [
                        ['name' => 'hsprof1', 'hotspot-address' => '192.168.3.2'],
                    ],
                ];
            }
            if ($cmd === '/ip/address/print') {
                return [
                    '!re' => [
                        ['address' => '192.168.3.2/24'],
                        ['address' => '192.168.2.1/24'],
                    ],
                ];
            }

            return ['!done' => []];
        });

        // Because 192.168.3.1 conflicts with Hotspot on 192.168.3.2, it should pick an alternative like 10.20.0.1
        $safeLocal = $mockApi->getSafePppoeLocalAddress($router);
        $this->assertNotEquals('192.168.3.1', $safeLocal);
        $this->assertEquals('10.20.0.1', $safeLocal);
    }

    public function test_sync_customer_to_mikrotik_automatically_sets_local_address(): void
    {
        $router = Router::create([
            'name' => 'Router Test Local Address',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Paket Test 10M',
            'type' => 'pppoe',
            'speed_rate' => '10M/10M',
            'mikrotik_profile' => 'TEST-10M',
            'price' => 100000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-LOCAL-001',
            'name' => 'Ahmad Pelanggan',
            'phone' => '081299887766',
            'router_id' => $router->id,
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'ahmad_pppoe',
            'pppoe_password' => 'secret123',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        $mockApi = $this->getMockBuilder(MikrotikApiService::class)
            ->onlyMethods(['connect', 'comm', 'syncPppSecret'])
            ->getMock();

        $mockApi->method('connect')->willReturn(true);
        $mockApi->method('comm')->willReturn(['!re' => [['name' => 'TEST-10M']]]);

        $mockApi->expects($this->once())
            ->method('syncPppSecret')
            ->with(
                $this->anything(),
                $this->callback(function ($secretData) {
                    return $secretData['name'] === 'ahmad_pppoe'
                        && isset($secretData['local-address'])
                        && ! empty($secretData['local-address'])
                        && $secretData['local-address'] === '10.20.0.1';
                })
            )
            ->willReturn(true);

        $res = $mockApi->syncCustomerToMikrotik($customer);
        $this->assertTrue($res['success']);
    }

    public function test_get_next_available_pppoe_ip_allocates_sequential_ips(): void
    {
        $router = Router::create([
            'name' => 'Router Sequential IP',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $mockApi = $this->getMockBuilder(MikrotikApiService::class)
            ->onlyMethods(['connect', 'comm'])
            ->getMock();

        $mockApi->method('connect')->willReturn(true);
        $mockApi->method('comm')->willReturn(['!re' => []]);

        // First customer gets .2 (since .1 is gateway local address)
        $firstIp = $mockApi->getNextAvailablePppoeIp($router);
        $this->assertEquals('10.20.0.2', $firstIp);

        Customer::create([
            'customer_number' => 'CUST-IP-001',
            'name' => 'Customer 1',
            'phone' => '081234567891',
            'connection_type' => 'pppoe',
            'pppoe_username' => 'cust1',
            'ip_address' => '10.20.0.2',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        // Second customer should get .3
        $secondIp = $mockApi->getNextAvailablePppoeIp($router);
        $this->assertEquals('10.20.0.3', $secondIp);
    }

    public function test_create_secret_in_pppoe_manager_includes_local_and_remote_address(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Secret Local',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $mockApi = $this->createMock(MikrotikApiService::class);
        $mockApi->method('connect')->willReturn(true);
        $mockApi->method('getPppProfiles')->willReturn([['name' => 'default']]);
        $mockApi->method('getSafePppoeLocalAddress')->willReturn('10.20.0.1');
        $mockApi->method('getNextAvailablePppoeIp')->willReturn('10.20.0.2');

        $mockApi->expects($this->once())
            ->method('syncPppSecret')
            ->with(
                $this->anything(),
                $this->callback(function ($secretData) {
                    return $secretData['name'] === 'user_with_local'
                        && ($secretData['local-address'] ?? '') === '10.20.0.1'
                        && ($secretData['remote-address'] ?? '') === '10.20.0.2';
                })
            )
            ->willReturn(true);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        Livewire::test(PppoeManager::class)
            ->callAction('createSecret', [
                'router_id' => $router->id,
                'name' => 'user_with_local',
                'password' => 'secret123',
                'profile' => 'default',
                'local_address' => '10.20.0.1',
                'remote_address' => '10.20.0.2',
                'service' => 'pppoe',
            ])
            ->assertHasNoActionErrors();

        // Customer created should also have ip_address = 10.20.0.2
        $cust = Customer::where('pppoe_username', 'user_with_local')->first();
        $this->assertNotNull($cust);
        $this->assertEquals('10.20.0.2', $cust->ip_address);
    }

    public function test_app_settings_persists_pppoe_subnet_configuration(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AppSettings::class)
            ->fillForm([
                'pppoe_local_address' => '10.30.0.1',
                'pppoe_pool_range' => '10.30.0.2-10.30.0.254',
                'pppoe_dns_servers' => '1.1.1.1,8.8.4.4',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('10.30.0.1', IspSetting::get('pppoe_local_address'));
        $this->assertEquals('10.30.0.2-10.30.0.254', IspSetting::get('pppoe_pool_range'));
        $this->assertEquals('1.1.1.1,8.8.4.4', IspSetting::get('pppoe_dns_servers'));
    }
}
