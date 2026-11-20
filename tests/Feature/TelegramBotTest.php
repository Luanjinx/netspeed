<?php

namespace Tests\Feature;

use App\Filament\Pages\TelegramBotManager;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Payment;
use App\Models\Router;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Notification\TelegramService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        IspSetting::set('telegram_bot_token', '123456789:ABCDEF1234567890');
        IspSetting::set('telegram_admin_chat_id', '-100987654321');
        IspSetting::set('telegram_bot_enabled', '1');
        IspSetting::set('telegram_notify_payment', '1');
        IspSetting::set('telegram_notify_ticket', '1');
        IspSetting::set('telegram_notify_router', '1');
        IspSetting::set('telegram_notify_fup', '1');
    }

    protected function createTestRouter(string $name = 'Router Core'): Router
    {
        return Router::create([
            'name' => $name,
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => '',
            'is_active' => true,
        ]);
    }

    protected function createTestPackage(string $name = 'Home 20 Mbps'): InternetPackage
    {
        return InternetPackage::create([
            'name' => $name,
            'type' => 'pppoe',
            'speed_rate' => '20M/20M',
            'mikrotik_profile' => 'HOME-20M',
            'price' => 150000,
            'validity_days' => 30,
            'is_active' => true,
        ]);
    }

    protected function createTestCustomer(array $attributes = []): Customer
    {
        $router = $attributes['router_id'] ?? $this->createTestRouter()->id;
        $package = $attributes['package_id'] ?? $this->createTestPackage()->id;

        return Customer::create(array_merge([
            'router_id' => $router,
            'package_id' => $package,
            'customer_number' => 'CUST-'.rand(1000, 9999),
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'pppoe_username' => 'budi_pppoe',
            'status' => 'active',
        ], $attributes));
    }

    public function test_telegram_service_checks_configuration_and_sends_message(): void
    {
        Http::fake([
            'https://api.telegram.org/bot123456789:ABCDEF1234567890/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 101,
                    'text' => 'Halo Tester',
                ],
            ], 200),
            'https://api.telegram.org/bot123456789:ABCDEF1234567890/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 998877,
                    'is_bot' => true,
                    'first_name' => 'ISP Billing Bot',
                    'username' => 'isp_test_bot',
                ],
            ], 200),
        ]);

        $service = app(TelegramService::class);

        $this->assertTrue($service->isEnabled());
        $this->assertEquals('123456789:ABCDEF1234567890', $service->getBotToken());
        $this->assertEquals('-100987654321', $service->getAdminChatId());

        $me = $service->getMe();
        $this->assertTrue($me['ok']);
        $this->assertEquals('isp_test_bot', $me['result']['username']);

        $res = $service->sendToAdmin('Halo Tester');
        $this->assertTrue($res['ok']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                $request['chat_id'] === '-100987654321';
        });
    }

    public function test_telegram_webhook_receives_start_command(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
        ]);

        $payload = [
            'update_id' => 12345,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 11223344,
                    'first_name' => 'Ahmad',
                    'username' => 'ahmad_noc',
                ],
                'chat' => [
                    'id' => 11223344,
                    'type' => 'private',
                ],
                'text' => '/start',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                $request['chat_id'] === 11223344 &&
                str_contains($request['text'], 'Selamat datang');
        });
    }

    public function test_telegram_webhook_handles_status_command(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
        ]);

        $this->createTestCustomer(['name' => 'Cust 1', 'status' => 'active', 'customer_number' => 'C-01']);
        $this->createTestCustomer(['name' => 'Cust 2', 'status' => 'active', 'customer_number' => 'C-02']);
        $this->createTestCustomer(['name' => 'Cust 3', 'status' => 'isolated', 'customer_number' => 'C-03']);

        $payload = [
            'update_id' => 12346,
            'message' => [
                'message_id' => 2,
                'chat' => [
                    'id' => 99999,
                ],
                'text' => '/status',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'RINGKASAN STATUS SISTEM') &&
                str_contains($request['text'], 'Total Pelanggan: <b>3</b>');
        });
    }

    public function test_telegram_webhook_handles_cek_command(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
        ]);

        $package = $this->createTestPackage('Speed 20 Mbps');
        $customer = $this->createTestCustomer([
            'customer_number' => 'CUST-0099',
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'pppoe_username' => 'budi_pppoe',
            'package_id' => $package->id,
            'status' => 'active',
        ]);

        $payload = [
            'update_id' => 12347,
            'message' => [
                'message_id' => 3,
                'chat' => ['id' => 99999],
                'text' => '/cek budi_pppoe',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                str_contains($request['text'], 'Budi Santoso') &&
                str_contains($request['text'], 'CUST-0099') &&
                str_contains($request['text'], 'Speed 20 Mbps');
        });
    }

    public function test_telegram_webhook_handles_tagihan_and_router_commands(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
        ]);

        $router = $this->createTestRouter('MikroTik Core NOC');

        // 1. /router command
        $this->postJson('/api/telegram/webhook', [
            'update_id' => 12348,
            'message' => [
                'message_id' => 4,
                'chat' => ['id' => 99999],
                'text' => '/router',
            ],
        ])->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'MikroTik Core NOC') &&
                str_contains($request['text'], '192.168.88.1');
        });

        // 2. /tagihan command
        $customer = $this->createTestCustomer(['router_id' => $router->id]);
        Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $customer->id,
            'package_id' => $customer->package_id,
            'period' => '2026-11',
            'amount' => 150000,
            'unique_code' => 123,
            'total_amount' => 150123,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'update_id' => 12349,
            'message' => [
                'message_id' => 5,
                'chat' => ['id' => 99999],
                'text' => '/tagihan',
            ],
        ])->assertStatus(200);

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'INV-TEST-001') &&
                str_contains($request['text'], '150.123');
        });
    }

    public function test_payment_and_support_ticket_events_send_telegram_alerts(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response(['ok' => true], 200),
        ]);

        $customer = $this->createTestCustomer(['name' => 'Rina Wijaya']);

        // 1. Support Ticket Created
        SupportTicket::create([
            'ticket_number' => 'TCK-202611-001',
            'customer_id' => $customer->id,
            'subject' => 'Internet Lambat',
            'description' => 'Mohon dicek redaman optik',
            'priority' => 'high',
            'status' => 'open',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'TIKET GANGGUAN / KELUHAN BARU') &&
                str_contains($request['text'], 'TCK-202611-001') &&
                str_contains($request['text'], 'Internet Lambat');
        });

        // 2. Payment Notification
        $invoice = Invoice::create([
            'invoice_number' => 'INV-PAY-002',
            'customer_id' => $customer->id,
            'package_id' => $customer->package_id,
            'period' => '2026-11',
            'amount' => 200000,
            'unique_code' => 0,
            'total_amount' => 200000,
            'status' => 'paid',
            'due_date' => now(),
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-REF-999',
            'amount_paid' => 200000,
            'payment_method' => 'qris',
            'is_verified' => true,
        ]);

        $service = app(TelegramService::class);
        $service->notifyPaymentReceived($payment);

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'PEMBAYARAN DITERIMA & LUNAS') &&
                str_contains($request['text'], 'INV-PAY-002') &&
                str_contains($request['text'], '200.000');
        });
    }

    public function test_telegram_bot_manager_page_renders_successfully(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 12345678,
                    'is_bot' => true,
                    'first_name' => 'NOC Telegram Bot',
                    'username' => 'noc_isp_bot',
                ],
            ], 200),
        ]);

        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(TelegramBotManager::class)
            ->assertSuccessful()
            ->assertSee('Manajemen & Integrasi Bot Telegram')
            ->assertSee('Kredensial & Autentikasi Bot Telegram');
    }

    public function test_artisan_telegram_command_runs_info_and_test(): void
    {
        Http::fake([
            'https://api.telegram.org/bot123456789:ABCDEF1234567890/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 123456,
                    'first_name' => 'TestBot',
                    'username' => 'test_bot',
                ],
            ], 200),
            'https://api.telegram.org/bot123456789:ABCDEF1234567890/getWebhookInfo' => Http::response([
                'ok' => true,
                'result' => [
                    'url' => 'https://example.com/api/telegram/webhook',
                    'pending_update_count' => 0,
                ],
            ], 200),
            'https://api.telegram.org/bot123456789:ABCDEF1234567890/sendMessage' => Http::response([
                'ok' => true,
            ], 200),
        ]);

        $this->artisan('telegram:bot --info')
            ->assertExitCode(0);

        $this->artisan('telegram:bot --test')
            ->assertExitCode(0);
    }
}
