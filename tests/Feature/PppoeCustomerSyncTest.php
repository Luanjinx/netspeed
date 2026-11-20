<?php

namespace Tests\Feature;

use App\Filament\Pages\PppoeManager;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Router;
use App\Models\User;
use App\Services\Router\MikrotikApiService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PppoeCustomerSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_pppoe_secret_in_pppoe_manager_automatically_creates_customer(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Sync Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Paket Hemat PPPoE',
            'type' => 'pppoe',
            'speed_rate' => '10M/10M',
            'mikrotik_profile' => 'HEMAT-10M',
            'price' => 100000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        // Mock MikrotikApiService so it returns success on syncPppSecret and getPppProfiles
        $mockApi = $this->createMock(MikrotikApiService::class);
        $mockApi->method('connect')->willReturn(true);
        $mockApi->method('getPppProfiles')->willReturn([
            ['name' => 'default'],
            ['name' => 'HEMAT-10M'],
        ]);
        $mockApi->method('syncPppSecret')
            ->willReturn(true);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        // Call createSecret action on PppoeManager without specifying existing customer_id
        Livewire::test(PppoeManager::class)
            ->callAction('createSecret', [
                'router_id' => $router->id,
                'customer_name' => 'Pelanggan Baru PPPoE',
                'customer_phone' => '081299998888',
                'name' => 'pelanggan_baru',
                'password' => 'secret123',
                'profile' => 'HEMAT-10M',
                'service' => 'pppoe',
                'comment' => 'Daftar baru via admin',
            ])
            ->assertHasNoActionErrors();

        // Verify customer record was automatically created in database
        $createdCustomer = Customer::where('pppoe_username', 'pelanggan_baru')->first();
        $this->assertNotNull($createdCustomer);
        $this->assertEquals('Pelanggan Baru PPPoE', $createdCustomer->name);
        $this->assertEquals('081299998888', $createdCustomer->phone);
        $this->assertEquals($router->id, $createdCustomer->router_id);
        $this->assertEquals($package->id, $createdCustomer->package_id);
        $this->assertEquals('secret123', $createdCustomer->pppoe_password);
        $this->assertEquals('active', $createdCustomer->status);
    }

    public function test_create_customer_pppoe_triggers_sync_to_mikrotik(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Customer Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Home Basic 10M',
            'type' => 'pppoe',
            'speed_rate' => '10M/10M',
            'mikrotik_profile' => 'PROFILE-10M',
            'price' => 100000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $mockApi = $this->createMock(MikrotikApiService::class);
        $mockApi->expects($this->once())
            ->method('syncCustomerToMikrotik')
            ->willReturn([
                'success' => true,
                'message' => 'User berhasil disinkronkan',
            ]);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'customer_number' => 'CUST-202611-999',
                'name' => 'Budi Santoso Baru',
                'phone' => '081234567890',
                'router_id' => $router->id,
                'package_id' => $package->id,
                'connection_type' => 'pppoe',
                'pppoe_username' => 'budi_baru',
                'pppoe_password' => 'budi123',
                'status' => 'active',
                'due_date_day' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(CustomerResource::getUrl('index'));

        $this->assertDatabaseHas('customers', [
            'pppoe_username' => 'budi_baru',
        ]);
    }

    public function test_deleting_customer_removes_pppoe_secret_from_mikrotik(): void
    {
        $router = Router::create([
            'name' => 'Router Delete Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-DEL-001',
            'name' => 'Hapus Saya',
            'phone' => '081234567899',
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'hapus_saya',
            'pppoe_password' => 'pass123',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        $mockApi = $this->createMock(MikrotikApiService::class);
        $mockApi->expects($this->once())
            ->method('removePppSecret')
            ->with($this->anything(), 'hapus_saya')
            ->willReturn(true);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        $customer->delete();

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_sync_customer_auto_creates_missing_profile_on_mikrotik(): void
    {
        $router = Router::create([
            'name' => 'Router Profile Auto Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Ultra Gamer 100M',
            'type' => 'pppoe',
            'speed_rate' => '100M/100M',
            'mikrotik_profile' => 'GAMER-100M',
            'price' => 300000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-PROF-001',
            'name' => 'Gamer User',
            'phone' => '081234567888',
            'router_id' => $router->id,
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'gamer100',
            'pppoe_password' => 'gamer123',
            'status' => 'active',
            'due_date_day' => 10,
        ]);

        $mockApi = $this->getMockBuilder(MikrotikApiService::class)
            ->onlyMethods(['connect', 'comm', 'syncPppSecret'])
            ->getMock();

        $mockApi->method('connect')->willReturn(true);

        // When checking for profile GAMER-100M, return empty (not found)
        // Then when adding profile, expect /ppp/profile/add call
        $mockApi->expects($this->atLeastOnce())
            ->method('comm')
            ->willReturnCallback(function ($cmd, $params = []) {
                if ($cmd === '/ppp/profile/print') {
                    return ['!re' => []]; // Profile not found
                }
                if ($cmd === '/ppp/profile/add') {
                    $this->assertEquals('GAMER-100M', $params['name']);
                    $this->assertEquals('100M/100M', $params['rate-limit']);

                    return ['!done' => []];
                }

                return ['!done' => []];
            });

        $mockApi->expects($this->once())
            ->method('syncPppSecret')
            ->with($this->anything(), $this->callback(function ($secretData) {
                return $secretData['name'] === 'gamer100' && $secretData['profile'] === 'GAMER-100M';
            }))
            ->willReturn(true);

        $res = $mockApi->syncCustomerToMikrotik($customer);
        $this->assertTrue($res['success']);
        $this->assertStringContainsString('gamer100', $res['message']);
    }
}
