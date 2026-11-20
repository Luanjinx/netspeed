<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPortalWebTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private InternetPackage $package;

    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = Router::create([
            'name' => 'Core-RB4011',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);

        $this->package = InternetPackage::create([
            'name' => 'Home Basic 10 Mbps',
            'speed_rate' => '10M/10M',
            'price' => 150000,
            'type' => 'pppoe',
            'mikrotik_profile' => 'default',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'customer_number' => 'CUST-202611-880',
            'name' => 'Naura Ayu',
            'phone' => '081929212732',
            'email' => 'naura@gmail.com',
            'password' => 'password123',
            'router_id' => $this->router->id,
            'package_id' => $this->package->id,
            'status' => 'active',
            'due_date' => 10,
        ]);
    }

    public function test_guest_is_redirected_from_customer_dashboard_to_login(): void
    {
        $response = $this->get('/customer/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_customer_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Portal Pelanggan')
            ->assertSee('mascot-wifi-phone')
            ->assertSee('Email atau Nomor WhatsApp');
    }

    public function test_customer_can_login_via_web_form_with_email(): void
    {
        $response = $this->post('/login', [
            'login' => 'naura@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_customer_can_login_via_web_form_with_phone_variant(): void
    {
        // Customer phone is 081929212732, test logging in with +6281929212732
        $response = $this->post('/login', [
            'login' => '+6281929212732',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_customer_dashboard_displays_customer_details_and_invoice(): void
    {
        $user = User::where('email', 'naura@gmail.com')->first();
        $this->actingAs($user);

        Invoice::create([
            'invoice_number' => 'INV-202611-999',
            'customer_id' => $this->customer->id,
            'package_id' => $this->package->id,
            'period' => 'November 2026',
            'amount' => 150000,
            'total_amount' => 150000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
        ]);

        $response = $this->get('/customer/dashboard');

        $response->assertStatus(200)
            ->assertSee('Naura Ayu')
            ->assertSee('CUST-202611-880')
            ->assertSee('Home Basic 10 Mbps')
            ->assertSee('Tagihan Belum Dibayar')
            ->assertSee('INV-202611-999')
            ->assertSee('Rp 150.000');
    }

    public function test_customer_can_create_trouble_ticket(): void
    {
        $user = User::where('email', 'naura@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->post('/customer/tickets', [
            'subject' => 'Internet LOS Merah Berkedip',
            'priority' => 'urgent',
            'description' => 'Lampu modem LOS menyala merah sejak tadi siang setelah hujan badai.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('support_tickets', [
            'customer_id' => $this->customer->id,
            'subject' => 'Internet LOS Merah Berkedip',
            'priority' => 'urgent',
            'status' => 'open',
        ]);
    }

    public function test_customer_can_update_password(): void
    {
        $user = User::where('email', 'naura@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->post('/customer/profile/password', [
            'current_password' => 'password123',
            'password' => 'newsecret321',
            'password_confirmation' => 'newsecret321',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newsecret321', $user->fresh()->password));
        $this->assertTrue(Hash::check('newsecret321', $this->customer->fresh()->password));
    }

    public function test_customer_can_logout(): void
    {
        $user = User::where('email', 'naura@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
