<?php

namespace Tests\Feature;

use App\Filament\Resources\Technicians\Pages\ListTechnicians;
use App\Filament\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Odp;
use App\Models\Router;
use App\Models\SupportTicket;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TechnicianPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_manage_technicians_via_filament(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $tech = Technician::create([
            'name' => 'Budi Teknisi',
            'email' => 'budi.tech@isp.net',
            'phone' => '081299998888',
            'password' => bcrypt('password123'),
            'work_area' => 'Zona 1 Barat',
            'is_active' => true,
        ]);

        $this->assertEquals('technician', $tech->role);
        $this->assertStringStartsWith('TECH-', $tech->technician_code);

        Livewire::test(ListTechnicians::class)
            ->assertCanSeeTableRecords([$tech])
            ->assertSee('Budi Teknisi')
            ->assertSee('Zona 1 Barat');
    }

    public function test_admin_can_create_and_view_work_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $tech = Technician::create([
            'name' => 'Agus Lapangan',
            'email' => 'agus@isp.net',
            'phone' => '081299991111',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Home Fast 20M',
            'type' => 'pppoe',
            'speed_rate' => '20M',
            'mikrotik_profile' => 'HOME-20M',
            'price' => 200000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-WO-001',
            'name' => 'Calon Pelanggan Baru',
            'phone' => '081288887777',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'status' => 'pending',
        ]);

        $workOrder = WorkOrder::create([
            'type' => 'installation',
            'technician_id' => $tech->id,
            'customer_id' => $customer->id,
            'title' => 'Pasang Baru Paket 20M',
            'status' => 'pending',
            'scheduled_date' => now(),
        ]);

        $this->assertStringStartsWith('SPK-', $workOrder->order_number);

        Livewire::test(ListWorkOrders::class)
            ->assertCanSeeTableRecords([$workOrder])
            ->assertSee($workOrder->order_number)
            ->assertSee('Calon Pelanggan Baru');
    }

    public function test_technician_login_redirects_to_technician_dashboard(): void
    {
        $tech = Technician::create([
            'name' => 'Slamet Teknisi',
            'email' => 'slamet@isp.net',
            'phone' => '081211112222',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'slamet@isp.net',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('technician.dashboard'));
        $this->assertAuthenticatedAs($tech);
    }

    public function test_technician_can_view_and_complete_work_order_with_opm_redaman(): void
    {
        $tech = Technician::create([
            'name' => 'Joko Fiber',
            'email' => 'joko@isp.net',
            'phone' => '081233334444',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $package = InternetPackage::create([
            'name' => 'Paket Ultra 50M',
            'type' => 'pppoe',
            'speed_rate' => '50M',
            'mikrotik_profile' => 'ULTRA-50M',
            'price' => 350000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-PSB-001',
            'name' => 'Pelanggan PSB Joko',
            'phone' => '081255556666',
            'package_id' => $package->id,
            'connection_type' => 'pppoe',
            'status' => 'pending',
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0099',
            'customer_id' => $customer->id,
            'technician_id' => $tech->id,
            'subject' => 'Kendala Pemasangan',
            'description' => 'Tarik kabel FO dropcore',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $workOrder = WorkOrder::create([
            'type' => 'installation',
            'technician_id' => $tech->id,
            'customer_id' => $customer->id,
            'support_ticket_id' => $ticket->id,
            'title' => 'Instalasi PSB Joko',
            'status' => 'pending',
            'scheduled_date' => now(),
        ]);

        $this->actingAs($tech);

        // 1. Dashboard loads successfully
        $dashResponse = $this->get('/teknisi/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Instalasi PSB Joko');
        $dashResponse->assertSee($workOrder->order_number);

        // 2. View work order detail
        $detailResponse = $this->get("/teknisi/tugas/{$workOrder->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Pelanggan PSB Joko');

        // 3. Start work order
        $startResponse = $this->post("/teknisi/tugas/{$workOrder->id}/start");
        $startResponse->assertRedirect();
        $this->assertEquals('in_progress', $workOrder->fresh()->status);
        $this->assertEquals('in_progress', $ticket->fresh()->status);

        // 4. Complete work order with redaman OPM -18.7 dBm
        $completeResponse = $this->post("/teknisi/tugas/{$workOrder->id}/complete", [
            'optical_power_dbm' => '-18.7',
            'completion_notes' => 'Pemasangan sukses, redaman stabil di -18.7 dBm.',
        ]);

        $completeResponse->assertRedirect(route('technician.dashboard'));

        $freshOrder = $workOrder->fresh();
        $this->assertEquals('completed', $freshOrder->status);
        $this->assertEquals('-18.7', $freshOrder->optical_power_dbm);
        $this->assertNotNull($freshOrder->completed_at);

        // Customer must now be activated
        $freshCustomer = $customer->fresh();
        $this->assertEquals('active', $freshCustomer->status);
        $this->assertEquals('-18.7', $freshCustomer->optical_power_dbm);
        $this->assertNotNull($freshCustomer->install_date);

        // Ticket must be resolved
        $freshTicket = $ticket->fresh();
        $this->assertEquals('resolved', $freshTicket->status);
        $this->assertEquals('-18.7', $freshTicket->optical_power_dbm);
        $this->assertNotNull($freshTicket->resolved_at);
    }

    public function test_inactive_technician_cannot_access_portal(): void
    {
        $tech = Technician::create([
            'name' => 'Teknisi Nonaktif',
            'email' => 'nonaktif@isp.net',
            'phone' => '081200000000',
            'password' => bcrypt('secret123'),
            'is_active' => false,
        ]);

        $this->actingAs($tech);

        $response = $this->get('/teknisi/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_technician_can_view_pool_tickets_and_claim_ticket(): void
    {
        $tech = Technician::create([
            'name' => 'Doni Lapangan',
            'email' => 'doni@isp.net',
            'phone' => '081277778888',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-POOL-001',
            'name' => 'Warga Lapor Gangguan',
            'phone' => '081299990000',
            'connection_type' => 'pppoe',
            'status' => 'active',
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-POOL-1234',
            'customer_id' => $customer->id,
            'technician_id' => null, // Unassigned in pool
            'subject' => 'Internet Loss Merah',
            'description' => 'Lampu LOS berkedip merah sejak pagi.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $this->actingAs($tech);

        // 1. View Pool Tiket
        $poolResponse = $this->get('/teknisi/pool-tiket');
        $poolResponse->assertStatus(200);
        $poolResponse->assertSee('Internet Loss Merah');
        $poolResponse->assertSee('Warga Lapor Gangguan');

        // 2. Claim Ticket
        $claimResponse = $this->post("/teknisi/pool-tiket/{$ticket->id}/claim");
        $claimResponse->assertRedirect();

        // Ticket assigned to Doni and status in_progress
        $freshTicket = $ticket->fresh();
        $this->assertEquals($tech->id, $freshTicket->technician_id);
        $this->assertEquals('in_progress', $freshTicket->status);

        // Auto created WorkOrder exists
        $workOrder = WorkOrder::where('support_ticket_id', $ticket->id)->first();
        $this->assertNotNull($workOrder);
        $this->assertEquals($tech->id, $workOrder->technician_id);
        $this->assertEquals('repair', $workOrder->type);
        $this->assertEquals('in_progress', $workOrder->status);
    }

    public function test_technician_can_register_new_customer_from_field_psb(): void
    {
        $tech = Technician::create([
            'name' => 'Rian PSB',
            'email' => 'rian@isp.net',
            'phone' => '081234567890',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $router = Router::create([
            'name' => 'Core-MikroTik',
            'host' => '192.168.1.1',
            'username' => 'admin',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $odp = Odp::create([
            'name' => 'ODP-MAWAR-01',
            'code' => 'ODP-MWR-01',
            'address' => 'Jl. Mawar No. 5',
            'total_ports' => 8,
            'status' => 'active',
        ]);

        $package = InternetPackage::create([
            'name' => 'Fast Fiber 30M',
            'type' => 'pppoe',
            'speed_rate' => '30M',
            'mikrotik_profile' => 'FIBER-30M',
            'price' => 250000,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($tech);

        // 1. Check AJAX ODP Ports
        $ajaxResponse = $this->getJson("/teknisi/ajax/odp-ports/{$odp->id}");
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson([
            'success' => true,
            'odp_name' => 'ODP-MAWAR-01',
            'total_ports' => 8,
            'available_count' => 8,
        ]);

        // 2. Load Form Pasang Baru
        $formResponse = $this->get('/teknisi/pasang-baru');
        $formResponse->assertStatus(200);
        $formResponse->assertSee('Input Pelanggan Baru (PSB)');

        // 3. Post Store Customer
        $storeResponse = $this->post('/teknisi/pasang-baru', [
            'name' => 'Pak Hendra Pratama',
            'phone' => '081399887766',
            'email' => 'hendra@gmail.com',
            'address' => 'Jl. Mawar No. 7, Cluster Melati',
            'latitude' => -6.214500,
            'longitude' => 106.845100,
            'router_id' => $router->id,
            'package_id' => $package->id,
            'odp_id' => $odp->id,
            'odp_port' => 3,
            'pppoe_username' => 'hendra_net',
            'pppoe_password' => 'hendra123',
            'optical_power_dbm' => '-19.2',
        ]);

        $storeResponse->assertRedirect(route('technician.dashboard'));

        $customer = Customer::where('pppoe_username', 'hendra_net')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Pak Hendra Pratama', $customer->name);
        $this->assertEquals('active', $customer->status);
        $this->assertEquals(3, $customer->odp_port);
        $this->assertEquals('-19.2', $customer->optical_power_dbm);

        // Ensure auto created completed WorkOrder
        $workOrder = WorkOrder::where('customer_id', $customer->id)->first();
        $this->assertNotNull($workOrder);
        $this->assertEquals('completed', $workOrder->status);
        $this->assertEquals('installation', $workOrder->type);
        $this->assertEquals('-19.2', $workOrder->optical_power_dbm);
    }

    public function test_technician_can_view_monitoring_and_history(): void
    {
        $tech = Technician::create([
            'name' => 'Eko Monitoring',
            'email' => 'eko@isp.net',
            'phone' => '081266667777',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $this->actingAs($tech);

        // 1. Monitoring page
        $monResponse = $this->get('/teknisi/monitoring');
        $monResponse->assertStatus(200);
        $monResponse->assertSee('Monitoring Sistem');

        // 2. History page
        $histResponse = $this->get('/teknisi/riwayat');
        $histResponse->assertStatus(200);
        $histResponse->assertSee('Riwayat Penanganan Tugas Lapangan');

        // 3. Field Map
        $mapResponse = $this->get('/tech/map');
        $mapResponse->assertStatus(200);
    }
}
