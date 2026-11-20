<?php

namespace Tests\Feature;

use App\Filament\Pages\OneSignalManager;
use App\Models\Customer;
use App\Models\IspSetting;
use App\Models\User;
use App\Services\Notification\OneSignalService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class OneSignalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        IspSetting::set('onesignal_app_id', 'test-app-id-12345');
        IspSetting::set('onesignal_rest_api_key', 'test-rest-key-abcde');
        IspSetting::set('onesignal_enabled', '1');
    }

    public function test_onesignal_test_connection_successful(): void
    {
        Http::fake([
            'https://onesignal.com/api/v1/apps/*' => Http::response([
                'id' => 'test-app-id-12345',
                'name' => 'ISP Billing Mobile App',
                'players' => 45,
                'messageable_players' => 42,
                'updated_at' => now()->toIso8601String(),
            ], 200),
        ]);

        $service = app(OneSignalService::class);
        $res = $service->testConnection();

        $this->assertTrue($res['success']);
        $this->assertEquals('ISP Billing Mobile App', $res['app_name']);
        $this->assertEquals(45, $res['players_count']);
    }

    public function test_onesignal_send_broadcast_creates_push_notification_log(): void
    {
        Http::fake([
            'https://onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notif-uuid-999',
                'recipients' => 28,
            ], 200),
        ]);

        $service = app(OneSignalService::class);
        $res = $service->sendBroadcast('Pemeliharaan Jaringan', 'Malam ini akan ada maintenance core router.');

        $this->assertTrue($res['success']);
        $this->assertEquals('notif-uuid-999', $res['notification_id']);
        $this->assertEquals(28, $res['recipients']);

        $this->assertDatabaseHas('push_notifications', [
            'title' => 'Pemeliharaan Jaringan',
            'target_type' => 'all',
            'status' => 'sent',
            'recipients_count' => 28,
            'onesignal_id' => 'notif-uuid-999',
        ]);
    }

    public function test_onesignal_send_to_customer(): void
    {
        Http::fake([
            'https://onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notif-cust-123',
                'recipients' => 1,
            ], 200),
        ]);

        $customer = Customer::create([
            'customer_number' => 'CUST-0001',
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'status' => 'active',
        ]);

        $service = app(OneSignalService::class);
        $res = $service->sendToCustomer($customer, 'Tagihan Jatuh Tempo', 'Mohon segera bayar tagihan Anda.');

        $this->assertTrue($res['success']);
        $this->assertDatabaseHas('push_notifications', [
            'title' => 'Tagihan Jatuh Tempo',
            'target_type' => 'customer',
            'status' => 'sent',
            'onesignal_id' => 'notif-cust-123',
        ]);
    }

    public function test_onesignal_manager_filament_page_renders_successfully(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Http::fake([
            'https://onesignal.com/api/v1/apps/*' => Http::response([
                'name' => 'ISP Billing Mobile App',
                'players' => 10,
                'messageable_players' => 10,
            ], 200),
            'https://onesignal.com/api/v1/notifications' => Http::response([
                'id' => 'notif-livewire-123',
                'recipients' => 5,
            ], 200),
        ]);

        Livewire::test(OneSignalManager::class)
            ->assertSuccessful()
            ->assertSee('Manajemen Push Notifikasi OneSignal')
            ->assertSee('Perangkat Terdaftar')
            ->set('data.send_title', 'Notifikasi Pengujian')
            ->set('data.send_message', 'Isi pesan pengujian dari Filament.')
            ->set('data.send_target_type', 'all')
            ->call('sendNotification')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('push_notifications', [
            'title' => 'Notifikasi Pengujian',
            'status' => 'sent',
        ]);
    }

    public function test_customer_portal_api_syncs_onesignal_id(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'customer_number' => 'CUST-API-01',
            'user_id' => $user->id,
            'name' => 'Pelanggan Flutter',
            'phone' => '081299998888',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/customer/onesignal-id', [
            'onesignal_id' => 'player-id-device-xyz-123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'onesignal_id' => 'player-id-device-xyz-123',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'onesignal_id' => 'player-id-device-xyz-123',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'onesignal_id' => 'player-id-device-xyz-123',
        ]);
    }
}
