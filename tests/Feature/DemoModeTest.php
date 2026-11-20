<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\DemoModeServiceProvider;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_mode_is_disabled_by_default_in_testing(): void
    {
        $this->assertFalse(config('app.demo'));
    }

    public function test_demo_mode_halts_mutating_actions_when_active(): void
    {
        config(['app.demo' => true]);

        // Boot DemoModeServiceProvider
        (new DemoModeServiceProvider($this->app))->boot();

        $saveAction = Action::make('save');

        $this->expectException(Halt::class);
        $saveAction->call();
    }

    public function test_demo_mode_allows_viewing_actions(): void
    {
        config(['app.demo' => true]);

        // Boot DemoModeServiceProvider
        (new DemoModeServiceProvider($this->app))->boot();

        $viewAction = Action::make('view')->action(fn () => 'viewed');
        $result = $viewAction->call();

        $this->assertEquals('viewed', $result);
    }

    public function test_demo_mode_blocks_admin_post_mutation_requests(): void
    {
        config(['app.demo' => true]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/map-save-cable', [
            'coordinates' => [
                ['lat' => -6.2, 'lng' => 106.8],
            ],
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'error' => 'Mode Demo Aktif: Aksi penambahan, perubahan, dan penghapusan data dinonaktifkan.',
        ]);
    }

    public function test_demo_mode_allows_get_requests(): void
    {
        config(['app.demo' => true]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Mode Demo Aktif');
    }
}
