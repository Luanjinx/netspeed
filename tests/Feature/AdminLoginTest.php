<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_renders_successfully(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Portal Administrator');
        $response->assertSee('Masuk ke Panel NOC');
        $response->assertSee('ambient-glow');
        $response->assertSee('floating-mascot-login');
        $response->assertSee('mascot-wifi-phone.webp');
        $response->assertSee('Kembali ke Halaman Utama');
        $response->assertSee('Masuk ke Portal Pelanggan');
        $response->assertSee('fi-login-customer-card');
        $response->assertSee('fi-login-customer-btn');
        $response->assertSee('fi-login-back-link');
    }

    public function test_admin_user_can_authenticate_via_admin_login(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
    }
}
