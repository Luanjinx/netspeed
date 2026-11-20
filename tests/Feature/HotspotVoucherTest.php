<?php

namespace Tests\Feature;

use App\Filament\Pages\HotspotManager;
use App\Filament\Pages\PppoeManager;
use App\Filament\Pages\RouterMonitor;
use App\Filament\Resources\HotspotVouchers\Pages\CreateHotspotVoucher;
use App\Filament\Resources\HotspotVouchers\Pages\EditHotspotVoucher;
use App\Filament\Resources\HotspotVouchers\Pages\ListHotspotVouchers;
use App\Models\HotspotVoucher;
use App\Models\InternetPackage;
use App\Models\Router;
use App\Models\User;
use App\Services\Hotspot\VoucherService;
use App\Services\Router\MikrotikApiService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HotspotVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotspot_voucher_resource_and_monitoring_render_successfully(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Test Hotspot',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $voucher = HotspotVoucher::create([
            'router_id' => $router->id,
            'code' => 'TEST1234',
            'password' => 'TEST1234',
            'profile' => '1HARI-5K',
            'price' => 5000,
            'validity' => '1 Hari',
            'status' => 'unused',
        ]);

        // 1. List Hotspot Vouchers page
        Livewire::test(ListHotspotVouchers::class)
            ->assertSuccessful();

        // 2. Create Hotspot Voucher page
        Livewire::test(CreateHotspotVoucher::class)
            ->assertSuccessful();

        // 3. Edit Hotspot Voucher page
        Livewire::test(EditHotspotVoucher::class, ['record' => $voucher->getKey()])
            ->assertSuccessful();

        // 4. Hotspot Manager monitoring page (registered in sidebar nav)
        Livewire::test(HotspotManager::class)
            ->assertSuccessful();

        $this->assertTrue(HotspotManager::shouldRegisterNavigation());
        $this->assertTrue(PppoeManager::shouldRegisterNavigation());

        // 5. Dedicated PPPoE Manager page
        Livewire::test(PppoeManager::class)
            ->assertSuccessful()
            ->assertSee('Tambah Secret PPPoE');

        // 6. Unified Router Monitor page with tabs
        Livewire::test(RouterMonitor::class)
            ->assertSuccessful()
            ->assertSee('Trafik Interface')
            ->assertSee('Monitoring Real-time PPPoE')
            ->assertSee('Monitoring Real-time Hotspot')
            ->call('setTab', 'pppoe')
            ->assertSet('activeTab', 'pppoe')
            ->call('setTab', 'hotspot')
            ->assertSet('activeTab', 'hotspot')
            ->call('setTab', 'router')
            ->assertSet('activeTab', 'router');
    }

    public function test_prune_orphan_safeguards_system_users_and_cleans_non_db_users(): void
    {
        $mockApi = $this->createMock(MikrotikApiService::class);
        $mockApi->expects($this->once())
            ->method('pruneOrphanHotspotUsers')
            ->willReturn([
                'success' => true,
                'pruned' => ['GHOST_VOUCHER'],
                'count' => 1,
            ]);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        $router = Router::create([
            'name' => 'Router Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $voucherService = app(VoucherService::class);
        $res = $voucherService->pruneOrphanVouchers($router);

        $this->assertTrue($res['success']);
        $this->assertEquals(1, $res['count']);
        $this->assertEquals(['GHOST_VOUCHER'], $res['pruned']);
    }

    public function test_voucher_model_deleting_event_triggers_router_cleanup(): void
    {
        $mockApi = $this->createMock(MikrotikApiService::class);
        $mockApi->expects($this->once())
            ->method('removeHotspotUser')
            ->with($this->anything(), 'AUTO_DELETE_ME')
            ->willReturn(true);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        $router = Router::create([
            'name' => 'Router Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $voucher = HotspotVoucher::create([
            'router_id' => $router->id,
            'code' => 'AUTO_DELETE_ME',
            'password' => 'AUTO_DELETE_ME',
            'profile' => 'default',
            'price' => 5000,
            'validity' => '1 Hari',
            'status' => 'unused',
        ]);

        $voucher->delete();

        $this->assertDatabaseMissing('hotspot_vouchers', ['id' => $voucher->id]);
    }

    public function test_generate_batch_creates_vouchers_with_shared_users(): void
    {
        $router = Router::create([
            'name' => 'Router Test Shared Users',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $voucherService = app(VoucherService::class);
        $res = $voucherService->generateBatch(
            router: $router,
            profile: '1HARI-3USER',
            price: 10000,
            validity: '1 Hari',
            qty: 5,
            prefix: 'TEST-',
            charType: 'alphanumeric_upper',
            codeLength: 5,
            userEqualsPass: true,
            limitUptime: '1d',
            sharedUsers: 3
        );

        $this->assertEquals(5, $res['count']);
        $this->assertEquals(3, $res['shared_users']);

        $vouchers = HotspotVoucher::where('generated_batch', $res['batch_id'])->get();
        $this->assertCount(5, $vouchers);

        foreach ($vouchers as $v) {
            $this->assertEquals(3, $v->shared_users);
            $this->assertEquals('1HARI-3USER', $v->profile);
        }
    }

    public function test_voucher_print_renders_shared_users(): void
    {
        $router = Router::create([
            'name' => 'Router Print Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $batchId = 'BATCH-PRINT-3U';
        $voucher = HotspotVoucher::create([
            'router_id' => $router->id,
            'code' => 'PRINT3U',
            'password' => 'PRINT3U',
            'profile' => '3USER-PROFILE',
            'shared_users' => 3,
            'price' => 15000,
            'validity' => '1 Hari',
            'status' => 'unused',
            'generated_batch' => $batchId,
        ]);

        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        // Test single voucher print
        $this->get(route('print.voucher.single', $voucher->id))
            ->assertSuccessful()
            ->assertSee('3 Perangkat');

        // Test batch voucher print sheet
        $this->get(route('print.vouchers', $batchId))
            ->assertSuccessful()
            ->assertSee('3 Perangkat');
    }

    public function test_voucher_validity_and_metadata_automatically_follow_mikrotik_hotspot_profile(): void
    {
        $voucherService = app(VoucherService::class);

        // 1. 30HARI-60K
        $m30 = $voucherService->extractProfileMetadata('30HARI-60K');
        $this->assertEquals('30 Hari', $m30['validity']);
        $this->assertEquals(60000, $m30['price']);
        $this->assertEquals('30d', $m30['limit_uptime']);

        // 2. 7HARI-20K
        $m7 = $voucherService->extractProfileMetadata('7HARI-20K');
        $this->assertEquals('7 Hari', $m7['validity']);
        $this->assertEquals(20000, $m7['price']);
        $this->assertEquals('7d', $m7['limit_uptime']);

        // 3. 3JAM-3K
        $m3j = $voucherService->extractProfileMetadata('3JAM-3K');
        $this->assertEquals('3 Jam', $m3j['validity']);
        $this->assertEquals(3000, $m3j['price']);
        $this->assertEquals('3h', $m3j['limit_uptime']);

        // 4. 12JAM-10K
        $m12 = $voucherService->extractProfileMetadata('12JAM-10K');
        $this->assertEquals('12 Jam', $m12['validity']);
        $this->assertEquals(10000, $m12['price']);
        $this->assertEquals('12h', $m12['limit_uptime']);

        // 5. 30HARI-3USER-100K
        $m3u = $voucherService->extractProfileMetadata('30HARI-3USER-100K');
        $this->assertEquals('30 Hari', $m3u['validity']);
        $this->assertEquals(100000, $m3u['price']);
        $this->assertEquals(3, $m3u['shared_users']);

        // 6. Test Livewire CreateHotspotVoucher auto-adapts validity for 30HARI-60K
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Core RB4011',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        InternetPackage::create([
            'name' => 'Hotspot 30HARI-60K',
            'type' => 'hotspot',
            'speed_rate' => '5M/20M',
            'mikrotik_profile' => '30HARI-60K',
            'price' => 60000,
            'validity_days' => 30,
            'shared_users' => 1,
            'is_active' => true,
        ]);

        Livewire::test(CreateHotspotVoucher::class)
            ->set('data.router_id', $router->id)
            ->set('data.profile', '30HARI-60K')
            ->set('data.code', 'TEST30D')
            ->set('data.password', 'TEST30D')
            ->call('create')
            ->assertHasNoFormErrors();

        $createdVoucher = HotspotVoucher::where('code', 'TEST30D')->first();
        $this->assertNotNull($createdVoucher);
        $this->assertEquals('30 Hari', $createdVoucher->validity);
        $this->assertEquals(60000, (int) $createdVoucher->price);
    }
}
