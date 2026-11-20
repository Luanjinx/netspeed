<?php

namespace Tests\Feature;

use App\Filament\Resources\InternetPackages\InternetPackageResource;
use App\Filament\Resources\InternetPackages\Pages\CreateInternetPackage;
use App\Filament\Resources\InternetPackages\Pages\ListInternetPackages;
use App\Models\Router;
use App\Models\User;
use App\Services\Router\MikrotikApiService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackageProfileSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_pppoe_package_syncs_ppp_profile_to_mikrotik(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Test PPPoE',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $mockApi = $this->getMockBuilder(MikrotikApiService::class)
            ->onlyMethods(['syncPppProfile'])
            ->getMock();

        $mockApi->expects($this->atLeastOnce())
            ->method('syncPppProfile')
            ->with(
                $this->anything(),
                $this->callback(function ($data) {
                    return $data['name'] === 'SUPER-100M' && $data['rate_limit'] === '100M/100M';
                })
            )
            ->willReturn([
                'success' => true,
                'message' => 'Profil PPPoE berhasil dibuat',
            ]);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        Livewire::test(CreateInternetPackage::class)
            ->fillForm([
                'name' => 'Super Cepat 100M',
                'speed_rate' => '100M/100M',
                'price' => 500000,
                'validity_days' => 30,
                'type' => 'pppoe',
                'mikrotik_profile' => 'SUPER-100M',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(InternetPackageResource::getUrl('index'));

        $this->assertDatabaseHas('internet_packages', [
            'mikrotik_profile' => 'SUPER-100M',
            'type' => 'pppoe',
        ]);
    }

    public function test_creating_hotspot_package_syncs_hotspot_profile_to_mikrotik(): void
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

        $mockApi = $this->getMockBuilder(MikrotikApiService::class)
            ->onlyMethods(['syncHotspotUserProfile'])
            ->getMock();

        $mockApi->expects($this->atLeastOnce())
            ->method('syncHotspotUserProfile')
            ->with(
                $this->anything(),
                $this->callback(function ($data) {
                    return $data['name'] === 'VIP-2JAM' && $data['rate_limit'] === '5M/10M' && $data['shared_users'] == 2;
                })
            )
            ->willReturn([
                'success' => true,
                'message' => 'Profil Hotspot berhasil dibuat',
            ]);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        Livewire::test(CreateInternetPackage::class)
            ->fillForm([
                'name' => 'VIP 2 Jam',
                'speed_rate' => '5M/10M',
                'price' => 5000,
                'validity_days' => 1,
                'type' => 'hotspot',
                'shared_users' => 2,
                'mikrotik_profile' => 'VIP-2JAM',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(InternetPackageResource::getUrl('index'));

        $this->assertDatabaseHas('internet_packages', [
            'mikrotik_profile' => 'VIP-2JAM',
            'type' => 'hotspot',
            'shared_users' => 2,
        ]);
    }

    public function test_list_internet_packages_sync_all_action(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $mockApi = $this->createMock(MikrotikApiService::class);
        $mockApi->expects($this->once())
            ->method('syncAllInternetPackagesToRouters')
            ->willReturn([
                'total' => 5,
                'success' => 5,
                'failed' => 0,
                'details' => [],
            ]);

        $this->app->instance(MikrotikApiService::class, $mockApi);

        Livewire::test(ListInternetPackages::class)
            ->callAction('syncAllToMikrotik')
            ->assertHasNoActionErrors();
    }
}
