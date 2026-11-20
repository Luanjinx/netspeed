<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\User;
use App\Services\Billing\BillingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoAndProrataBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        IspSetting::set('use_unique_code', '0');

        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_promo_price_applied_and_cycles_counted(): void
    {
        $package = InternetPackage::create([
            'name' => 'Promo Speed 50M',
            'type' => 'pppoe',
            'speed_rate' => '50M/50M',
            'mikrotik_profile' => 'SPEED-50M',
            'price' => 300000,
            'promo_price' => 200000,
            'promo_cycles' => 2,
            'is_prorated' => false,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-TEST-001',
            'name' => 'Pelanggan Promo 1',
            'phone' => '081234567891',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'promo_user1',
            'pppoe_password' => 'secret123',
            'status' => 'active',
            'due_date_day' => 10,
            'promo_cycles_used' => 0,
        ]);

        $billingService = app(BillingService::class);

        // Cycle 1: Should use promo price (200,000)
        $inv1 = $billingService->generateInvoice($customer, '2026-01');
        $this->assertEquals(200000, $inv1->amount);
        $this->assertStringContainsString('AUTO: Promo siklus 1/2', $inv1->notes);
        $this->assertEquals(1, $customer->fresh()->promo_cycles_used);

        // Cycle 2: Should use promo price (200,000)
        $inv2 = $billingService->generateInvoice($customer, '2026-02');
        $this->assertEquals(200000, $inv2->amount);
        $this->assertStringContainsString('AUTO: Promo siklus 2/2', $inv2->notes);
        $this->assertEquals(2, $customer->fresh()->promo_cycles_used);

        // Cycle 3: Promo exhausted, should return to regular price (300,000)
        $inv3 = $billingService->generateInvoice($customer, '2026-03');
        $this->assertEquals(300000, $inv3->amount);
        $this->assertNull($inv3->notes);
        $this->assertEquals(2, $customer->fresh()->promo_cycles_used);
    }

    public function test_promo_cycles_used_is_reset_when_package_changes(): void
    {
        $package1 = InternetPackage::create([
            'name' => 'Paket A',
            'type' => 'pppoe',
            'speed_rate' => '20M',
            'mikrotik_profile' => 'PA',
            'price' => 200000,
            'promo_price' => 150000,
            'promo_cycles' => 3,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $package2 = InternetPackage::create([
            'name' => 'Paket B',
            'type' => 'pppoe',
            'speed_rate' => '50M',
            'mikrotik_profile' => 'PB',
            'price' => 350000,
            'promo_price' => 250000,
            'promo_cycles' => 3,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-RESET-001',
            'name' => 'Pelanggan Ganti Paket',
            'phone' => '081234567892',
            'package_id' => $package1->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'ganti_paket',
            'pppoe_password' => 'secret123',
            'status' => 'active',
            'promo_cycles_used' => 2,
        ]);

        $this->assertEquals(2, $customer->fresh()->promo_cycles_used);

        // Change package
        $customer->update(['package_id' => $package2->id]);

        $this->assertEquals(0, $customer->fresh()->promo_cycles_used);
    }

    public function test_prorata_calculation_for_first_invoice_in_installation_month(): void
    {
        // 30 days in November (2026-11)
        // Install date: 2026-11-16 -> remaining days = 30 - 16 + 1 = 15 days (50%)
        $package = InternetPackage::create([
            'name' => 'Paket Prorata 30M',
            'type' => 'pppoe',
            'speed_rate' => '30M/30M',
            'mikrotik_profile' => 'P-30M',
            'price' => 300000,
            'is_prorated' => true,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-PRORATA-001',
            'name' => 'Pelanggan Prorata 1',
            'phone' => '081234567893',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'prorata_user1',
            'pppoe_password' => 'secret123',
            'install_date' => '2026-11-16',
            'status' => 'active',
        ]);

        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($customer, '2026-11');

        // 15 / 30 * 300000 = 150000
        $this->assertEquals(150000, $invoice->amount);
        $this->assertStringContainsString('AUTO: Prorata bulan pasang (15/30 hari, tgl pasang: 16/11/2026)', $invoice->notes);

        // Next month (full month, no prorata)
        $invoiceNext = $billingService->generateInvoice($customer, '2026-12');
        $this->assertEquals(300000, $invoiceNext->amount);
    }

    public function test_combined_prorata_and_promo_calculation(): void
    {
        // November has 30 days. Installed on 2026-11-16 (15 remaining days = 50%)
        // Promo price: 200000 -> 50% = 100000
        $package = InternetPackage::create([
            'name' => 'Paket Combo 50M',
            'type' => 'pppoe',
            'speed_rate' => '50M',
            'mikrotik_profile' => 'P-50M',
            'price' => 400000,
            'promo_price' => 200000,
            'promo_cycles' => 3,
            'is_prorated' => true,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-COMBO-001',
            'name' => 'Pelanggan Combo',
            'phone' => '081234567894',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'combo_user1',
            'pppoe_password' => 'secret123',
            'install_date' => '2026-11-16',
            'promo_cycles_used' => 0,
            'status' => 'active',
        ]);

        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($customer, '2026-11');

        $this->assertEquals(100000, $invoice->amount);
        $this->assertStringContainsString('Promo siklus 1/3', $invoice->notes);
        $this->assertStringContainsString('Prorata bulan pasang (15/30 hari', $invoice->notes);
        $this->assertEquals(1, $customer->fresh()->promo_cycles_used);
    }

    public function test_retroactive_prorata_invoice_uses_regular_price_and_does_not_consume_promo(): void
    {
        // Installed in September (30 days) on 2026-09-15 -> 16 remaining days
        $package = InternetPackage::create([
            'name' => 'Paket Retro 100M',
            'type' => 'pppoe',
            'speed_rate' => '100M',
            'mikrotik_profile' => 'P-100M',
            'price' => 300000,
            'promo_price' => 150000,
            'promo_cycles' => 3,
            'is_prorated' => true,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-RETRO-001',
            'name' => 'Pelanggan Retro Susulan',
            'phone' => '081234567895',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'retro_user1',
            'pppoe_password' => 'secret123',
            'install_date' => '2026-09-15',
            'promo_cycles_used' => 0,
            'status' => 'active',
        ]);

        $billingService = app(BillingService::class);

        // Susulan prorata bulan pasang
        $invoice = $billingService->generateRetroactiveProrataInvoice($customer);

        // 16 / 30 * 300000 = 160000 (from regular price 300000, NOT promo price 150000)
        $this->assertEquals(160000, $invoice->amount);
        $this->assertEquals('2026-09', $invoice->period);
        $this->assertStringContainsString('AUTO: Susulan tagihan prorata bulan pasang (16/30 hari, tgl pasang: 15/09/2026) dari harga reguler Rp 300.000', $invoice->notes);

        // Counter promo_cycles_used is NOT incremented by retroactive susulan
        $this->assertEquals(0, $customer->fresh()->promo_cycles_used);

        // Subsequent call throws exception because invoice for install month already exists
        $this->expectException(\Exception::class);
        $billingService->generateRetroactiveProrataInvoice($customer);
    }

    public function test_admin_can_reset_promo_cycles(): void
    {
        $package = InternetPackage::create([
            'name' => 'Paket Reset',
            'type' => 'pppoe',
            'speed_rate' => '20M',
            'mikrotik_profile' => 'P-RESET',
            'price' => 200000,
            'promo_price' => 100000,
            'promo_cycles' => 3,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-ADMIN-RESET',
            'name' => 'Pelanggan Reset Promo',
            'phone' => '081234567896',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'admin_reset_user',
            'pppoe_password' => 'secret123',
            'promo_cycles_used' => 3,
            'status' => 'active',
        ]);

        $this->assertEquals(3, $customer->promo_cycles_used);

        // Admin resets promo cycles
        $customer->update(['promo_cycles_used' => 0]);

        $this->assertEquals(0, $customer->fresh()->promo_cycles_used);
    }

    public function test_filament_table_actions_for_reset_promo_and_susulan_prorata(): void
    {
        $package = InternetPackage::create([
            'name' => 'Paket Filament Test',
            'type' => 'pppoe',
            'speed_rate' => '10M',
            'mikrotik_profile' => 'P-FIL',
            'price' => 200000,
            'promo_price' => 100000,
            'promo_cycles' => 2,
            'is_prorated' => true,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-FIL-001',
            'name' => 'Pelanggan Table Action',
            'phone' => '081234567897',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'action_user',
            'pppoe_password' => 'secret123',
            'install_date' => '2026-10-15',
            'promo_cycles_used' => 2,
            'status' => 'active',
        ]);

        // 1. Test resetPromoCycles table action
        Livewire::test(ListCustomers::class)
            ->callTableAction('resetPromoCycles', $customer)
            ->assertHasNoTableActionErrors();

        $this->assertEquals(0, $customer->fresh()->promo_cycles_used);

        // 2. Test susulanProrata table action
        Livewire::test(ListCustomers::class)
            ->callTableAction('susulanProrata', $customer)
            ->assertHasNoTableActionErrors();

        $invoice = Invoice::where('customer_id', $customer->id)
            ->where('period', '2026-10')
            ->first();

        $this->assertNotNull($invoice);
        $this->assertStringContainsString('AUTO: Susulan tagihan prorata', $invoice->notes);
        // October has 31 days. Installed 15th -> 17 days remaining.
        // 17 / 31 * 200000 = 109677 (from regular price 200000)
        $this->assertEquals(109677, $invoice->amount);
    }
}
