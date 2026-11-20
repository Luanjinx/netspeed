<?php

namespace Tests\Feature;

use App\Filament\Resources\HotspotVouchers\HotspotVoucherResource;
use App\Filament\Resources\HotspotVouchers\Pages\CreateHotspotVoucher;
use App\Filament\Resources\HotspotVouchers\Pages\EditHotspotVoucher;
use App\Filament\Resources\InternetPackages\InternetPackageResource;
use App\Filament\Resources\InternetPackages\Pages\CreateInternetPackage;
use App\Filament\Resources\InternetPackages\Pages\EditInternetPackage;
use App\Models\HotspotVoucher;
use App\Models\InternetPackage;
use App\Models\Router;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_internet_package_redirects_to_index_page_after_save(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $package = InternetPackage::create([
            'name' => 'Paket Test 10M',
            'type' => 'pppoe',
            'speed_rate' => '10M/10M',
            'mikrotik_profile' => 'TEST-10M',
            'price' => 100000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $indexUrl = InternetPackageResource::getUrl('index');

        Livewire::test(EditInternetPackage::class, ['record' => $package->getKey()])
            ->fillForm([
                'name' => 'Paket Test 10M Renamed',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect($indexUrl);
    }

    public function test_create_internet_package_redirects_to_index_page_after_save(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $indexUrl = InternetPackageResource::getUrl('index');

        Livewire::test(CreateInternetPackage::class)
            ->fillForm([
                'name' => 'Paket Baru 20M',
                'type' => 'pppoe',
                'speed_rate' => '20M/20M',
                'mikrotik_profile' => 'BARU-20M',
                'price' => 200000,
                'validity_days' => 30,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect($indexUrl);
    }

    public function test_create_hotspot_voucher_redirects_to_index_page_after_save(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $indexUrl = HotspotVoucherResource::getUrl('index');

        Livewire::test(CreateHotspotVoucher::class)
            ->fillForm([
                'router_id' => $router->id,
                'profile' => 'default',
                'code' => 'TESTVOUCHER1',
                'password' => 'TESTVOUCHER1',
                'price' => 5000,
                'validity' => '1 Hari',
                'shared_users' => 1,
                'status' => 'unused',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect($indexUrl);
    }

    public function test_edit_hotspot_voucher_redirects_to_index_page_after_save(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $router = Router::create([
            'name' => 'Router Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $voucher = HotspotVoucher::create([
            'router_id' => $router->id,
            'profile' => 'default',
            'code' => 'TESTVOUCHER2',
            'password' => 'TESTVOUCHER2',
            'price' => 5000,
            'validity' => '1 Hari',
            'shared_users' => 1,
            'status' => 'unused',
        ]);

        $indexUrl = HotspotVoucherResource::getUrl('index');

        Livewire::test(EditHotspotVoucher::class, ['record' => $voucher->getKey()])
            ->fillForm([
                'price' => 10000,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect($indexUrl);
    }
}
