<?php

namespace Tests\Feature;

use App\Filament\Pages\PppoeManager;
use App\Models\Customer;
use App\Models\CustomerFupUsage;
use App\Models\InternetPackage;
use App\Models\Router;
use App\Models\User;
use App\Services\Bandwidth\FupService;
use App\Services\Notification\WhatsAppService;
use App\Services\Router\MikrotikApiService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class FupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_record_and_check_usage_accumulates_bytes_and_handles_reconnect(): void
    {
        $router = Router::create([
            'name' => 'Router Test FUP',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Paket FUP 100GB',
            'type' => 'pppoe',
            'speed_rate' => '10M/20M',
            'mikrotik_profile' => 'HOME-20M',
            'price' => 200000,
            'validity_days' => 30,
            'is_active' => true,
            'fup_enabled' => true,
            'fup_limit_gb' => 100,
            'fup_throttle_speed' => '2M/5M',
        ]);

        $customer = Customer::create([
            'router_id' => $router->id,
            'package_id' => $package->id,
            'customer_number' => 'CUST-001',
            'name' => 'Budi Santoso',
            'pppoe_username' => 'budi01',
            'phone' => '08123456789',
            'status' => 'active',
            'fup_status' => 'normal',
        ]);

        $service = app(FupService::class);

        // 1. Initial measurement (5 GB Rx, 5 GB Tx = ~10 GB total)
        // 5 GB = 5 * 1024 * 1024 * 1024 bytes = 5,368,709,120
        $fiveGb = 5 * 1024 * 1024 * 1024;
        $res1 = $service->recordAndCheckUsage($customer, $fiveGb, $fiveGb);

        $this->assertEquals('normal', $customer->fresh()->fup_status);
        $this->assertFalse($res1['fup_triggered']);

        $usage1 = CustomerFupUsage::where('customer_id', $customer->id)->first();
        $this->assertNotNull($usage1);
        $this->assertEquals(0, $usage1->total_bytes); // Delta on first measurement is 0 (last_seen set to initial)

        // 2. Second measurement: customer transfers 10 GB more (Tx goes from 5 GB to 15 GB, Rx from 5 GB to 15 GB)
        $fifteenGb = 15 * 1024 * 1024 * 1024;
        $res2 = $service->recordAndCheckUsage($customer, $fifteenGb, $fifteenGb);

        $usage2 = $usage1->fresh();
        // Delta Tx = 10 GB, Delta Rx = 10 GB => total delta = 20 GB
        $expectedBytes = 20 * 1024 * 1024 * 1024;
        $this->assertEquals($expectedBytes, $usage2->total_bytes);
        $this->assertEquals(20.0, $res2['usage_gb']);

        // 3. User reconnects (counter resets to 2 GB Rx and 3 GB Tx)
        $twoGb = 2 * 1024 * 1024 * 1024;
        $threeGb = 3 * 1024 * 1024 * 1024;
        $res3 = $service->recordAndCheckUsage($customer, $twoGb, $threeGb);

        $usage3 = $usage2->fresh();
        // Delta = 2 GB + 3 GB = 5 GB added to previous 20 GB = 25 GB
        $this->assertEquals(25.0, $res3['usage_gb']);
    }

    public function test_fup_triggers_and_throttles_speed_when_limit_exceeded(): void
    {
        $router = Router::create([
            'name' => 'Router Test FUP 2',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Paket FUP 50GB',
            'type' => 'pppoe',
            'speed_rate' => '10M/20M',
            'mikrotik_profile' => 'HOME-20M',
            'price' => 150000,
            'validity_days' => 30,
            'is_active' => true,
            'fup_enabled' => true,
            'fup_limit_gb' => 50,
            'fup_throttle_speed' => '2M/5M',
            'fup_mikrotik_profile' => 'PROFIL-FUP-5M',
        ]);

        $customer = Customer::create([
            'router_id' => $router->id,
            'package_id' => $package->id,
            'customer_number' => 'CUST-002',
            'name' => 'Siti Aminah',
            'pppoe_username' => 'siti02',
            'phone' => '08198765432',
            'status' => 'active',
            'fup_status' => 'normal',
        ]);

        // Mock MikrotikApiService
        $mockMikrotik = Mockery::mock(MikrotikApiService::class);
        $mockMikrotik->shouldReceive('connect')->with(Mockery::type(Router::class))->andReturn(true);
        $mockMikrotik->shouldReceive('comm')
            ->with('/ppp/secret/print', ['?name' => 'siti02'])
            ->andReturn([
                '!re' => [
                    ['.id' => '*1', 'name' => 'siti02', 'profile' => 'default'],
                ],
            ]);
        $mockMikrotik->shouldReceive('comm')
            ->with('/ppp/secret/set', Mockery::subset(['numbers' => '*1', 'profile' => 'PROFIL-FUP-5M']))
            ->andReturn(['!done' => []]);
        $mockMikrotik->shouldReceive('kickPppActive')->with('siti02')->andReturn(true);
        $mockMikrotik->shouldReceive('disconnect')->andReturnNull();

        // Mock WhatsAppService
        $mockWhatsapp = Mockery::mock(WhatsAppService::class);
        $mockWhatsapp->shouldReceive('sendFupAlert')
            ->once()
            ->with(Mockery::type(Customer::class), Mockery::any(), Mockery::any(), '2M/5M')
            ->andReturn(['success' => true]);

        $service = new FupService($mockMikrotik, $mockWhatsapp);

        // Pre-create usage with 49 GB
        $period = date('Y-m');
        $fortyNineGb = 49 * 1024 * 1024 * 1024;
        CustomerFupUsage::create([
            'customer_id' => $customer->id,
            'period' => $period,
            'bytes_download' => $fortyNineGb,
            'bytes_upload' => 0,
            'total_bytes' => $fortyNineGb,
            'last_seen_rx' => 0,
            'last_seen_tx' => 0,
        ]);

        // Next poll brings 5 GB more -> total 54 GB > 50 GB limit
        $fiveGb = 5 * 1024 * 1024 * 1024;
        $res = $service->recordAndCheckUsage($customer, 0, $fiveGb);

        $this->assertTrue($res['fup_triggered']);
        $this->assertEquals('fup_applied', $customer->fresh()->fup_status);
        $this->assertNotNull($customer->fresh()->fup_notified_at);

        $usage = CustomerFupUsage::where('customer_id', $customer->id)->where('period', $period)->first();
        $this->assertNotNull($usage->fup_applied_at);
    }

    public function test_restore_customer_speed(): void
    {
        $router = Router::create([
            'name' => 'Router Test FUP 3',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Paket Reguler 20M',
            'type' => 'pppoe',
            'speed_rate' => '10M/20M',
            'price' => 200000,
            'validity_days' => 30,
            'is_active' => true,
            'mikrotik_profile' => 'HOME-20M',
            'fup_enabled' => true,
            'fup_limit_gb' => 100,
            'fup_throttle_speed' => '2M/5M',
        ]);

        $customer = Customer::create([
            'router_id' => $router->id,
            'package_id' => $package->id,
            'customer_number' => 'CUST-003',
            'name' => 'Ahmad Dani',
            'pppoe_username' => 'ahmad03',
            'phone' => '081233445566',
            'status' => 'active',
            'fup_status' => 'fup_applied',
        ]);

        // Mock MikrotikApiService
        $mockMikrotik = Mockery::mock(MikrotikApiService::class);
        $mockMikrotik->shouldReceive('connect')->with(Mockery::type(Router::class))->andReturn(true);
        $mockMikrotik->shouldReceive('comm')
            ->with('/ppp/secret/print', ['?name' => 'ahmad03'])
            ->andReturn([
                '!re' => [
                    ['.id' => '*2', 'name' => 'ahmad03', 'profile' => 'PROFIL-FUP-5M'],
                ],
            ]);
        $mockMikrotik->shouldReceive('comm')
            ->with('/ppp/secret/set', Mockery::subset(['numbers' => '*2', 'profile' => 'HOME-20M']))
            ->andReturn(['!done' => []]);
        $mockMikrotik->shouldReceive('kickPppActive')->with('ahmad03')->andReturn(true);
        $mockMikrotik->shouldReceive('disconnect')->andReturnNull();

        // Mock WhatsAppService
        $mockWhatsapp = Mockery::mock(WhatsAppService::class);
        $mockWhatsapp->shouldReceive('sendFupRestored')
            ->once()
            ->with(Mockery::type(Customer::class))
            ->andReturn(['success' => true]);

        $service = new FupService($mockMikrotik, $mockWhatsapp);
        $success = $service->restoreCustomerSpeed($customer, true);

        $this->assertTrue($success);
        $this->assertEquals('normal', $customer->fresh()->fup_status);
    }

    public function test_check_fup_quota_command_runs_successfully(): void
    {
        $this->artisan('fup:check')
            ->assertSuccessful();
    }

    public function test_reset_monthly_fup_command_runs_successfully(): void
    {
        $this->artisan('fup:reset-cycle')
            ->assertSuccessful();
    }

    public function test_pppoe_manager_page_renders_fup_features(): void
    {
        $router = Router::create([
            'name' => 'Router Test UI',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        Livewire::test(PppoeManager::class)
            ->assertSuccessful()
            ->assertSee('Tambah Secret PPPoE')
            ->assertSee('Cek & Terapkan FUP');
    }
}
