<?php

namespace Tests\Feature;

use App\Filament\Resources\InternetPackages\Pages\CreateInternetPackage;
use App\Filament\Resources\InternetPackages\Pages\EditInternetPackage;
use App\Filament\Resources\InternetPackages\Pages\ListInternetPackages;
use App\Models\InternetPackage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InternetPackageImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_internet_package_can_have_image(): void
    {
        $package = InternetPackage::create([
            'name' => 'Ultra Gamer 100M',
            'speed_rate' => '100M/100M',
            'price' => 500000,
            'validity_days' => 30,
            'mikrotik_profile' => 'PROFILE-100M',
            'type' => 'pppoe',
            'image' => 'packages/gamer-banner.jpg',
            'is_active' => true,
        ]);

        $this->assertEquals('packages/gamer-banner.jpg', $package->fresh()->image);

        // Check on landing page
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Ultra Gamer 100M');
        $response->assertSee('packages/gamer-banner.jpg');
    }

    public function test_admin_can_upload_image_for_internet_package_in_filament(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $file = UploadedFile::fake()->image('promo-package.jpg', 600, 400);

        Livewire::test(CreateInternetPackage::class)
            ->fillForm([
                'name' => 'Paket Juara 50 Mbps',
                'speed_rate' => '50M/50M',
                'price' => 275000,
                'validity_days' => 30,
                'type' => 'pppoe',
                'mikrotik_profile' => 'PROFILE-50M',
                'image' => $file,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $package = InternetPackage::where('name', 'Paket Juara 50 Mbps')->first();
        $this->assertNotNull($package);
        $this->assertNotNull($package->image);

        // Edit package and replace or keep image
        Livewire::test(EditInternetPackage::class, ['record' => $package->id])
            ->assertSuccessful()
            ->assertFormSet([
                'name' => 'Paket Juara 50 Mbps',
            ]);

        // Table list renders without issues
        Livewire::test(ListInternetPackages::class)
            ->assertCanSeeTableRecords([$package])
            ->assertSee('Paket Juara 50 Mbps');
    }
}
