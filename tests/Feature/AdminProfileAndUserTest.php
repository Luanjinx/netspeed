<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Models\AdminUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProfileAndUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_profile_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@isp.net',
            'name' => 'Super Admin',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $response = $this->get('/admin/profile');
        $response->assertStatus(200);
        $response->assertSee('Profil & Keamanan Akun');
        $response->assertSee('admin@isp.net');
    }

    public function test_admin_can_update_email_and_password_via_profile_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'old_admin@isp.net',
            'name' => 'Admin Lama',
            'password' => Hash::make('oldpassword123'),
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'Admin Baru Update',
                'email' => 'new_admin@isp.net',
                'currentPassword' => 'oldpassword123',
                'password' => 'newpassword123',
                'passwordConfirmation' => 'newpassword123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $freshAdmin = $admin->fresh();
        $this->assertEquals('Admin Baru Update', $freshAdmin->name);
        $this->assertEquals('new_admin@isp.net', $freshAdmin->email);
        $this->assertTrue(Hash::check('newpassword123', $freshAdmin->password));
    }

    public function test_admin_can_manage_admins_via_resource(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'main_admin@isp.net',
            'name' => 'Main Admin',
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // 1. List admin users
        Livewire::test(ListAdminUsers::class)
            ->assertSee('Main Admin')
            ->assertSee('main_admin@isp.net');

        // 2. Create another admin user
        Livewire::test(CreateAdminUser::class)
            ->fillForm([
                'name' => 'Admin Cabang',
                'email' => 'cabang@isp.net',
                'phone' => '081233445566',
                'password' => 'cabang12345',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $newAdmin = AdminUser::where('email', 'cabang@isp.net')->first();
        $this->assertNotNull($newAdmin);
        $this->assertEquals('admin', $newAdmin->role);
        $this->assertTrue(Hash::check('cabang12345', $newAdmin->password));

        // 3. Edit another admin user (change email and password)
        Livewire::test(EditAdminUser::class, ['record' => $newAdmin->id])
            ->fillForm([
                'email' => 'cabang_edited@isp.net',
                'password' => 'newsecret999',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $freshNewAdmin = $newAdmin->fresh();
        $this->assertEquals('cabang_edited@isp.net', $freshNewAdmin->email);
        $this->assertTrue(Hash::check('newsecret999', $freshNewAdmin->password));
    }
}
