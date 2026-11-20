<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Router;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_successfully(): void
    {
        $package = InternetPackage::create([
            'name' => 'Home Ultra 20 Mbps',
            'type' => 'pppoe',
            'speed_rate' => '20M/20M',
            'mikrotik_profile' => 'HOME-20M',
            'price' => 150000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $hotspot = InternetPackage::create([
            'name' => 'Hotspot 1HARI-5K',
            'type' => 'hotspot',
            'speed_rate' => '2M/5M',
            'mikrotik_profile' => '1HARI-5K',
            'price' => 5000,
            'validity_days' => 1,
            'shared_users' => 3,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Home Ultra 20 Mbps')
            ->assertSee('Hotspot 1HARI-5K')
            ->assertSee('Cek Tagihan')
            ->assertSee('Daftar Pasang Baru Sekarang');
    }

    public function test_landing_check_bill_returns_customer_and_invoice_data(): void
    {
        $router = Router::create([
            'name' => 'Router Landing Test',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Home Basic 10 Mbps',
            'type' => 'pppoe',
            'speed_rate' => '10M/10M',
            'mikrotik_profile' => 'HOME-10M',
            'price' => 100000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'router_id' => $router->id,
            'package_id' => $package->id,
            'customer_number' => 'CUST-LANDING-01',
            'name' => 'Bambang Sudirman',
            'email' => 'bambang.landing@gmail.com',
            'phone' => '081234567890',
            'pppoe_username' => 'bambang01',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'invoice_number' => 'INV-202611-001',
            'period' => '2026-11',
            'amount' => 100000,
            'total_amount' => 100000,
            'due_date' => '2026-11-20',
            'status' => 'unpaid',
        ]);

        // Search by customer number
        $response = $this->postJson('/cek-tagihan', [
            'search' => 'CUST-LANDING-01',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.name', 'Bambang Sudirman')
            ->assertJsonPath('invoice.invoice_number', 'INV-202611-001')
            ->assertJsonPath('invoice.is_unpaid', true);

        // Search by phone
        $responsePhone = $this->postJson('/cek-tagihan', [
            'search' => '081234567890',
        ]);

        $responsePhone->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.name', 'Bambang Sudirman');

        // Search by email
        $responseEmail = $this->postJson('/cek-tagihan', [
            'search' => 'bambang.landing@gmail.com',
        ]);

        $responseEmail->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.name', 'Bambang Sudirman');
    }

    public function test_landing_check_bill_isolated_customer_without_unpaid_invoices(): void
    {
        $router = Router::create([
            'name' => 'Router Landing Test 2',
            'host' => '127.0.0.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Home Basic 15 Mbps',
            'type' => 'pppoe',
            'speed_rate' => '5M/15M',
            'mikrotik_profile' => 'HOME-15M',
            'price' => 50000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'router_id' => $router->id,
            'package_id' => $package->id,
            'customer_number' => 'CUST-ISO-01',
            'name' => 'Naura Isolated',
            'email' => 'naura.iso@gmail.com',
            'phone' => '081999888777',
            'pppoe_username' => 'naura01',
            'status' => 'isolated',
        ]);

        $response = $this->postJson('/cek-tagihan', [
            'search' => '081999888777',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.name', 'Naura Isolated')
            ->assertJsonPath('customer.status', 'isolated')
            ->assertJsonPath('has_unpaid', false)
            ->assertJsonPath('invoice', null);
    }

    public function test_landing_check_bill_returns_404_for_unknown_customer(): void
    {
        $response = $this->postJson('/cek-tagihan', [
            'search' => 'NON_EXISTING_USER_999',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_landing_register_lead_submits_successfully(): void
    {
        $package = InternetPackage::create([
            'name' => 'Home Ultra 20 Mbps',
            'type' => 'pppoe',
            'speed_rate' => '20M/20M',
            'mikrotik_profile' => 'HOME-20M',
            'price' => 150000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->postJson('/daftar-langganan', [
            'name' => 'Joko Widodo',
            'phone' => '081987654321',
            'package_id' => $package->id,
            'address' => 'Jl. Mawar No. 10, RT 01/02',
            'notes' => 'Survey akhir pekan',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertStringContainsString('https://wa.me/', $response->json('wa_url'));
        $this->assertStringContainsString('Joko Widodo', urldecode($response->json('wa_url')));
    }

    public function test_landing_page_renders_dynamic_company_name_from_settings(): void
    {
        IspSetting::set('company_name', 'Nexstream Fiber Optik');
        IspSetting::set('company_phone', '089988776655');

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Nexstream Fiber Optik')
            ->assertSee('Nexstream Fiber Optik Core Gateway')
            ->assertSee('089988776655');
    }
}
