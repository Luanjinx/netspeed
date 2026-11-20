<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_customer_can_login_with_email_and_password(): void
    {
        $router = Router::create([
            'name' => 'Core-1',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'admin',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Home Basic 10M',
            'speed_rate' => '10M/10M',
            'price' => 150000,
            'type' => 'pppoe',
            'mikrotik_profile' => 'default',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-202611-101',
            'name' => 'Naura Ayu',
            'phone' => '081929212732',
            'email' => 'naura@gmail.com',
            'password' => 'secret123',
            'router_id' => $router->id,
            'package_id' => $package->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'naura@gmail.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil.',
            ])
            ->assertJsonPath('data.customer.customer_number', 'CUST-202611-101')
            ->assertJsonPath('data.customer.name', 'Naura Ayu');

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_customer_can_login_with_phone_number_and_password(): void
    {
        $customer = Customer::create([
            'customer_number' => 'CUST-202611-102',
            'name' => 'Budi Pratama',
            'phone' => '081234567890',
            'email' => 'budi@gmail.com',
            'password' => 'passBudi2026',
            'status' => 'active',
        ]);

        // Login using 0812... format
        $response1 = $this->postJson('/api/auth/login', [
            'login' => '081234567890',
            'password' => 'passBudi2026',
        ]);
        $response1->assertStatus(200)
            ->assertJsonPath('data.customer.name', 'Budi Pratama');

        // Login using 62812... international format
        $response2 = $this->postJson('/api/auth/login', [
            'login' => '6281234567890',
            'password' => 'passBudi2026',
        ]);
        $response2->assertStatus(200)
            ->assertJsonPath('data.customer.name', 'Budi Pratama');
    }

    public function test_customer_can_login_with_customer_number_and_password(): void
    {
        Customer::create([
            'customer_number' => 'CUST-202611-880',
            'name' => 'Citra Lestari',
            'phone' => '087812345678',
            'password' => 'citraPass99',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'CUST-202611-880',
            'password' => 'citraPass99',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.customer.name', 'Citra Lestari');
    }

    public function test_customer_login_fails_with_invalid_password(): void
    {
        Customer::create([
            'customer_number' => 'CUST-202611-103',
            'name' => 'Dewi Sartika',
            'phone' => '081399998888',
            'email' => 'dewi@gmail.com',
            'password' => 'correctPassword',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'dewi@gmail.com',
            'password' => 'wrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email/No WhatsApp atau password salah.',
            ]);
    }

    public function test_customer_saving_password_syncs_with_user(): void
    {
        $customer = Customer::create([
            'customer_number' => 'CUST-202611-104',
            'name' => 'Eko Prasetyo',
            'phone' => '081512345678',
            'email' => 'eko@gmail.com',
            'password' => 'initialPass123',
            'status' => 'active',
        ]);

        $this->assertNotNull($customer->user_id);
        $user = User::find($customer->user_id);
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('initialPass123', $user->password));

        // Update customer password
        $customer->update(['password' => 'newUpdatedPassword456']);
        $user->refresh();
        $this->assertTrue(Hash::check('newUpdatedPassword456', $user->password));
    }
}
