<?php

namespace App\Services\Notification;

use App\Models\Customer;
use App\Models\CustomerFupUsage;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Payment;
use App\Models\Router;
use App\Models\SupportTicket;
use App\Models\WorkOrder;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Get the configured Telegram Bot Token.
     */
    public function getBotToken(): string
    {
        return (string) IspSetting::get('telegram_bot_token', '');
    }

    /**
     * Get the configured Admin/NOC Chat ID.
     */
    public function getAdminChatId(): string
    {
        return (string) IspSetting::get('telegram_admin_chat_id', '');
    }

    /**
     * Check if Telegram Bot integration is enabled.
     */
    public function isEnabled(): bool
    {
        return IspSetting::get('telegram_bot_enabled', '1') === '1' && ! empty($this->getBotToken());
    }

    /**
     * Make an HTTP request to Telegram Bot API.
     */
    public function makeRequest(string $method, array $params = [], ?string $token = null): array
    {
        $botToken = $token ?: $this->getBotToken();

        if (empty($botToken)) {
            return [
                'ok' => false,
                'description' => 'Bot Token Telegram belum dikonfigurasi.',
            ];
        }

        $apiUrl = "https://api.telegram.org/bot{$botToken}/{$method}";

        try {
            $response = Http::timeout(10)->post($apiUrl, $params);
            $data = $response->json();

            if ($response->successful() && is_array($data)) {
                return $data;
            }

            Log::warning("Telegram API call [{$method}] failed: HTTP {$response->status()} - ".$response->body());

            return [
                'ok' => false,
                'description' => $data['description'] ?? ("HTTP Error {$response->status()}"),
                'raw' => $data,
            ];
        } catch (Exception $e) {
            Log::error("Telegram API call [{$method}] exception: ".$e->getMessage());

            return [
                'ok' => false,
                'description' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test connection and get bot identity.
     */
    public function getMe(?string $token = null): array
    {
        return $this->makeRequest('getMe', [], $token);
    }

    /**
     * Send message to a specific chat ID.
     */
    public function sendMessage(
        string|int $chatId,
        string $text,
        string $parseMode = 'HTML',
        ?array $inlineKeyboard = null,
        bool $disableWebPagePreview = true
    ): array {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => $disableWebPagePreview,
        ];

        if ($inlineKeyboard !== null) {
            $params['reply_markup'] = json_encode([
                'inline_keyboard' => $inlineKeyboard,
            ]);
        }

        return $this->makeRequest('sendMessage', $params);
    }

    /**
     * Send message to Admin / NOC chat.
     */
    public function sendToAdmin(
        string $text,
        string $parseMode = 'HTML',
        ?array $inlineKeyboard = null
    ): array {
        $adminChatId = $this->getAdminChatId();

        if (empty($adminChatId)) {
            return [
                'ok' => false,
                'description' => 'Chat ID Admin Telegram belum dikonfigurasi.',
            ];
        }

        return $this->sendMessage($adminChatId, $text, $parseMode, $inlineKeyboard);
    }

    /**
     * Set webhook URL for Telegram updates.
     */
    public function setWebhook(?string $url = null): array
    {
        $webhookUrl = $url ?: url('/api/telegram/webhook');

        return $this->makeRequest('setWebhook', [
            'url' => $webhookUrl,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
    }

    /**
     * Delete webhook (switch back to polling).
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        return $this->makeRequest('deleteWebhook', [
            'drop_pending_updates' => $dropPendingUpdates,
        ]);
    }

    /**
     * Get webhook diagnostic information.
     */
    public function getWebhookInfo(): array
    {
        return $this->makeRequest('getWebhookInfo');
    }

    /**
     * Get updates manually via polling.
     */
    public function getUpdates(int $offset = 0, int $limit = 20, int $timeout = 0): array
    {
        return $this->makeRequest('getUpdates', [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout,
        ]);
    }

    /**
     * Send Payment Receipt notification to Admin/NOC.
     */
    public function notifyPaymentReceived(Payment $payment): array
    {
        if (! $this->isEnabled() || IspSetting::get('telegram_notify_payment', '1') !== '1') {
            return ['ok' => false, 'description' => 'Notifikasi pembayaran Telegram dinonaktifkan.'];
        }

        $invoice = $payment->invoice;
        $customer = $invoice?->customer;
        $ispName = IspSetting::get('company_name', 'ISP Billing');

        $text = "💰 <b>PEMBAYARAN DITERIMA & LUNAS</b>\n\n"
            .'• No. Tagihan: <code>'.($invoice?->invoice_number ?? '-')."</code>\n"
            .'• Pelanggan: <b>'.($customer?->name ?? 'Pelanggan').'</b> ('.($customer?->customer_number ?? '-').")\n"
            .'• Paket: <b>'.($customer?->package?->name ?? '-')."</b>\n"
            .'• Nominal: <b>Rp '.number_format($payment->amount_paid, 0, ',', '.')."</b>\n"
            .'• Metode: <b>'.strtoupper($payment->payment_method)."</b>\n"
            .'• Router: <b>'.($customer?->router?->name ?? '-')."</b> (PPPoE Aktif)\n"
            .'• Waktu: <b>'.now()->format('d/m/Y H:i:s')." WIB</b>\n\n"
            .'<i>'.htmlspecialchars($ispName).' Billing System</i>';

        $buttons = [
            [
                ['text' => '👤 Cek Pelanggan', 'callback_data' => 'cek_'.($customer?->customer_number ?? '')],
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
            ],
        ];

        return $this->sendToAdmin($text, 'HTML', $buttons);
    }

    /**
     * Send Invoice Created notification to Admin/NOC.
     */
    public function notifyInvoiceCreated(Invoice $invoice): array
    {
        if (! $this->isEnabled() || IspSetting::get('telegram_notify_invoice', '0') !== '1') {
            return ['ok' => false, 'description' => 'Notifikasi invoice Telegram dinonaktifkan.'];
        }

        $customer = $invoice->customer;
        $dueDate = $invoice->due_date ? date('d-m-Y', strtotime($invoice->due_date)) : '-';

        $text = "🧾 <b>TAGIHAN BARU DITERBITKAN</b>\n\n"
            ."• No. Invoice: <code>{$invoice->invoice_number}</code>\n"
            .'• Pelanggan: <b>'.($customer?->name ?? '-')."</b>\n"
            ."• Periode: <b>{$invoice->period}</b>\n"
            .'• Total: <b>Rp '.number_format($invoice->total_amount, 0, ',', '.')."</b>\n"
            ."• Jatuh Tempo: <b>{$dueDate}</b>";

        return $this->sendToAdmin($text);
    }

    /**
     * Send Support Ticket Created notification to NOC.
     */
    public function notifyTicketCreated(SupportTicket $ticket): array
    {
        if (! $this->isEnabled() || IspSetting::get('telegram_notify_ticket', '1') !== '1') {
            return ['ok' => false, 'description' => 'Notifikasi tiket Telegram dinonaktifkan.'];
        }

        $customer = $ticket->customer;
        $priorityEmoji = match ($ticket->priority) {
            'high', 'critical' => '🚨',
            'medium' => '⚠️',
            default => 'ℹ️',
        };

        $text = "{$priorityEmoji} <b>TIKET GANGGUAN / KELUHAN BARU</b>\n\n"
            ."• No. Tiket: <code>{$ticket->ticket_number}</code>\n"
            .'• Pelanggan: <b>'.($customer?->name ?? '-').'</b> ('.($customer?->phone ?? '-').")\n"
            .'• Subjek: <b>'.htmlspecialchars($ticket->subject)."</b>\n"
            .'• Prioritas: <b>'.strtoupper($ticket->priority)."</b>\n"
            ."• Pesan:\n<i>".htmlspecialchars($ticket->description)."</i>\n\n"
            .'• Waktu: <b>'.now()->format('d/m/Y H:i:s').' WIB</b>';

        $buttons = [
            [
                ['text' => '👤 Cek Pelanggan', 'callback_data' => 'cek_'.($customer?->customer_number ?? '')],
            ],
        ];

        return $this->sendToAdmin($text, 'HTML', $buttons);
    }

    /**
     * Send Router Down / Up alert to NOC.
     */
    public function notifyRouterStatus(Router $router, bool $isOnline, ?string $reason = null): array
    {
        if (! $this->isEnabled() || IspSetting::get('telegram_notify_router', '1') !== '1') {
            return ['ok' => false, 'description' => 'Notifikasi router Telegram dinonaktifkan.'];
        }

        $statusTitle = $isOnline ? '🟢 ROUTER PULIH / ONLINE' : '🔴 ALERT: ROUTER OFFLINE / DOWN';

        $text = "<b>{$statusTitle}</b>\n\n"
            .'• Router: <b>'.htmlspecialchars($router->name)."</b>\n"
            ."• IP Host: <code>{$router->host}:{$router->port}</code>\n"
            .'• Status: <b>'.($isOnline ? 'Online / Merespon' : 'Tidak Merespon / Timeout')."</b>\n"
            .'• Keterangan: <i>'.htmlspecialchars($reason ?: ($isOnline ? 'Koneksi normal' : 'Gagal tersambung'))."</i>\n"
            .'• Waktu: <b>'.now()->format('d/m/Y H:i:s').' WIB</b>';

        $buttons = [
            [
                ['text' => '🌐 Cek Semua Router', 'callback_data' => 'cmd_router'],
            ],
        ];

        return $this->sendToAdmin($text, 'HTML', $buttons);
    }

    /**
     * Send FUP Quota Exceeded alert.
     */
    public function notifyFupTriggered(Customer $customer, float $usageGb, float $limitGb, string $throttleSpeed): array
    {
        if (! $this->isEnabled() || IspSetting::get('telegram_notify_fup', '1') !== '1') {
            return ['ok' => false, 'description' => 'Notifikasi FUP Telegram dinonaktifkan.'];
        }

        $text = "📉 <b>PERINGATAN FUP PPPOE TERCAPAI</b>\n\n"
            .'• Pelanggan: <b>'.htmlspecialchars($customer->name)."</b> (<code>{$customer->pppoe_username}</code>)\n"
            .'• Paket: <b>'.($customer->package?->name ?? '-')."</b>\n"
            ."• Kuota Terpakai: <b>{$usageGb} GB / {$limitGb} GB</b>\n"
            ."• Penyesuaian Speed: Dibatasi ke <b>{$throttleSpeed}</b>\n"
            .'• Router: <b>'.($customer->router?->name ?? '-')."</b>\n"
            .'• Waktu: <b>'.now()->format('d/m/Y H:i').' WIB</b>';

        $buttons = [
            [
                ['text' => '👤 Cek Pelanggan', 'callback_data' => 'cek_'.$customer->customer_number],
            ],
        ];

        return $this->sendToAdmin($text, 'HTML', $buttons);
    }

    /**
     * Notify Admin/NOC about a new customer registration from Mobile App.
     */
    public function notifyNewRegistration(Customer $customer, WorkOrder $workOrder): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => false, 'description' => 'Telegram bot dinonaktifkan.'];
        }

        $packageName = $customer->package?->name ?? '-';
        $packagePrice = $customer->package ? 'Rp '.number_format($customer->package->price, 0, ',', '.') : '-';

        $text = "🎉 <b>PENDAFTARAN PASANG BARU (PSB)</b>\n\n"
            .'• No. Pelanggan: <code>'.htmlspecialchars($customer->customer_number)."</code>\n"
            .'• No. SPK: <code>'.htmlspecialchars($workOrder->order_number)."</code>\n"
            .'• Nama Calon: <b>'.htmlspecialchars($customer->name)."</b>\n"
            .'• No. WhatsApp: <code>'.htmlspecialchars($customer->phone)."</code>\n"
            .'• Pilihan Paket: <b>'.htmlspecialchars($packageName)."</b> ({$packagePrice})\n"
            .'• Alamat: <i>'.htmlspecialchars($customer->address ?? '-')."</i>\n"
            .($customer->latitude && $customer->longitude ? "• Koordinat: <code>{$customer->latitude}, {$customer->longitude}</code>\n" : '')
            .'• Waktu: <b>'.now()->format('d/m/Y H:i').' WIB</b>';

        $buttons = [
            [
                ['text' => '📋 Buka SPK di Panel', 'url' => url('/admin/work-orders')],
            ],
        ];

        return $this->sendToAdmin($text, 'HTML', $buttons);
    }

    /**
     * Handle incoming Telegram webhook updates or polling updates.
     */
    public function handleUpdate(array $update): ?array
    {
        // 1. Handle Callback Queries (Inline Button Clicks)
        if (isset($update['callback_query'])) {
            $cb = $update['callback_query'];
            $chatId = $cb['message']['chat']['id'] ?? null;
            $data = $cb['data'] ?? '';
            $cbId = $cb['id'] ?? null;

            if ($cbId) {
                $this->makeRequest('answerCallbackQuery', ['callback_query_id' => $cbId]);
            }

            if (! $chatId) {
                return null;
            }

            if (str_starts_with($data, 'cek_')) {
                $query = substr($data, 4);

                return $this->cmdCek($chatId, $query);
            }

            return match ($data) {
                'cmd_status' => $this->cmdStatus($chatId),
                'cmd_tagihan' => $this->cmdTagihan($chatId),
                'cmd_router' => $this->cmdRouter($chatId),
                'cmd_fup' => $this->cmdFup($chatId),
                'cmd_help' => $this->cmdHelp($chatId),
                default => $this->sendMessage($chatId, 'Perintah tombol tidak dikenal.'),
            };
        }

        // 2. Handle Messages
        if (! isset($update['message'])) {
            return null;
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');

        if (! $chatId || empty($text)) {
            return null;
        }

        // Parse command and argument
        $parts = explode(' ', $text, 2);
        $cmd = strtolower($parts[0]);
        // Remove @bot_username if sent in groups (e.g., /status@isp_bot)
        if (str_contains($cmd, '@')) {
            $cmd = explode('@', $cmd)[0];
        }
        $arg = trim($parts[1] ?? '');

        return match ($cmd) {
            '/start' => $this->cmdStart($chatId, $message['from'] ?? []),
            '/help', '/bantuan' => $this->cmdHelp($chatId),
            '/id' => $this->cmdId($chatId),
            '/status' => $this->cmdStatus($chatId),
            '/cek' => $this->cmdCek($chatId, $arg),
            '/tagihan' => $this->cmdTagihan($chatId),
            '/router' => $this->cmdRouter($chatId),
            '/fup' => $this->cmdFup($chatId),
            default => $this->cmdDefault($chatId, $text),
        };
    }

    /**
     * Command: /start
     */
    protected function cmdStart(int|string $chatId, array $from): array
    {
        $name = htmlspecialchars($from['first_name'] ?? 'Sahabat');
        $ispName = htmlspecialchars(IspSetting::get('company_name', 'NETSPEED WI-FI BROADBAND'));

        $text = "👋 <b>Halo {$name}!</b>\n\n"
            ."Selamat datang di <b>{$ispName} Telegram Assistant</b>.\n"
            ."Bot ini terhubung langsung ke Server Billing & NOC MikroTik Anda.\n\n"
            ."🆔 <b>Chat ID Anda:</b> <code>{$chatId}</code>\n"
            ."(Salin ID di atas jika ingin didaftarkan sebagai Admin Chat ID)\n\n"
            .'Silakan pilih menu cepat di bawah ini:';

        $keyboard = [
            [
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
                ['text' => '💳 Tagihan Belum Lunas', 'callback_data' => 'cmd_tagihan'],
            ],
            [
                ['text' => '🌐 Router MikroTik', 'callback_data' => 'cmd_router'],
                ['text' => '📉 Monitor FUP', 'callback_data' => 'cmd_fup'],
            ],
            [
                ['text' => '❓ Panduan Perintah', 'callback_data' => 'cmd_help'],
            ],
        ];

        return $this->sendMessage($chatId, $text, 'HTML', $keyboard);
    }

    /**
     * Command: /help or /bantuan
     */
    protected function cmdHelp(int|string $chatId): array
    {
        $text = "📋 <b>DAFTAR PERINTAH BOT TELEGRAM ISP</b>\n\n"
            ."• <b>/status</b> - Ringkasan pelanggan, router, dan penagihan\n"
            ."• <b>/cek [nama / username / no_layanan]</b> - Cek detail pelanggan\n"
            ."• <b>/tagihan</b> - Daftar 5 tagihan tertunggak terbaru\n"
            ."• <b>/router</b> - Cek status router MikroTik yang terdaftar\n"
            ."• <b>/fup</b> - Ringkasan pemakaian FUP pelanggan bulan ini\n"
            ."• <b>/id</b> - Melihat Chat ID Telegram Anda saat ini\n"
            ."• <b>/help</b> - Menampilkan pesan panduan ini\n\n"
            ."<i>Contoh pencarian pelanggan:</i>\n"
            .'<code>/cek Budi</code> atau <code>/cek user_pppoe</code>';

        $keyboard = [
            [
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
                ['text' => '💳 Tagihan', 'callback_data' => 'cmd_tagihan'],
            ],
        ];

        return $this->sendMessage($chatId, $text, 'HTML', $keyboard);
    }

    /**
     * Command: /id
     */
    protected function cmdId(int|string $chatId): array
    {
        $text = "🆔 <b>INFORMASI TELEGRAM CHAT ID</b>\n\n"
            ."Chat ID Anda adalah:\n"
            ."<code>{$chatId}</code>\n\n"
            ."💡 <i>Tips: Tempelkan angka ini pada kolom 'Chat ID Admin / NOC' di menu Pengaturan Bot Telegram pada admin panel.</i>";

        return $this->sendMessage($chatId, $text);
    }

    /**
     * Command: /status
     */
    protected function cmdStatus(int|string $chatId): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $isolatedCustomers = Customer::where('status', 'isolated')->count();

        $totalRouters = Router::count();
        $activeRouters = Router::where('is_active', true)->count();

        $unpaidInvoices = Invoice::where('status', 'unpaid');
        $unpaidCount = $unpaidInvoices->count();
        $unpaidSum = (float) $unpaidInvoices->sum('total_amount');

        $openTickets = SupportTicket::whereIn('status', ['open', 'in_progress', 'pending'])->count();
        $ispName = htmlspecialchars(IspSetting::get('company_name', 'ISP Billing'));

        $text = "📊 <b>RINGKASAN STATUS SISTEM - {$ispName}</b>\n\n"
            ."👥 <b>Data Pelanggan:</b>\n"
            ."• Total Pelanggan: <b>{$totalCustomers}</b>\n"
            ."• Pelanggan Aktif: <b>{$activeCustomers}</b> 🟢\n"
            ."• Pelanggan Terisolir: <b>{$isolatedCustomers}</b> 🔴\n\n"
            ."🌐 <b>Router MikroTik:</b>\n"
            ."• Total Router: <b>{$totalRouters}</b> (Aktif: {$activeRouters})\n\n"
            ."💳 <b>Keuangan & Penagihan:</b>\n"
            ."• Tagihan Belum Lunas: <b>{$unpaidCount} Tagihan</b>\n"
            .'• Total Tertunggak: <b>Rp '.number_format($unpaidSum, 0, ',', '.')."</b>\n\n"
            ."🎫 <b>Tiket Gangguan:</b>\n"
            ."• Tiket Perlu Ditangani: <b>{$openTickets} Tiket</b>\n\n"
            .'🕒 <i>Diperbarui: '.now()->format('d/m/Y H:i:s').' WIB</i>';

        $keyboard = [
            [
                ['text' => '💳 Cek Tagihan Unpaid', 'callback_data' => 'cmd_tagihan'],
                ['text' => '🌐 Cek Router', 'callback_data' => 'cmd_router'],
            ],
        ];

        return $this->sendMessage($chatId, $text, 'HTML', $keyboard);
    }

    /**
     * Command: /cek <query>
     */
    protected function cmdCek(int|string $chatId, string $query): array
    {
        if (empty($query)) {
            $text = "⚠️ <b>Format perintah salah!</b>\n\n"
                ."Ketik perintah dengan kata kunci pencarian, contoh:\n"
                ."<code>/cek Budi</code>\n"
                ."<code>/cek 081234567890</code>\n"
                .'<code>/cek user_pppoe</code>';

            return $this->sendMessage($chatId, $text);
        }

        $customer = Customer::with(['package', 'router', 'invoices'])
            ->where('customer_number', 'like', "%{$query}%")
            ->orWhere('pppoe_username', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->first();

        if (! $customer) {
            $text = "❌ <b>Pelanggan tidak ditemukan!</b>\n\n"
                .'Pencarian untuk: <code>'.htmlspecialchars($query).'</code> tidak menghasilkan data.';

            return $this->sendMessage($chatId, $text);
        }

        $statusBadge = match ($customer->status) {
            'active' => '🟢 AKTIF',
            'isolated' => '🔴 TERISOLIR',
            'inactive' => '⚪ NON-AKTIF',
            default => strtoupper($customer->status),
        };

        $lastInvoice = $customer->invoices()->orderBy('id', 'desc')->first();
        $invStatus = $lastInvoice
            ? ($lastInvoice->status === 'paid' ? '✅ Lunas' : '⏳ Belum Bayar (Rp '.number_format($lastInvoice->total_amount, 0, ',', '.').')')
            : 'Belum ada tagihan';

        $text = "👤 <b>DATA PELANGGAN</b>\n\n"
            ."• No. Pelanggan: <code>{$customer->customer_number}</code>\n"
            .'• Nama: <b>'.htmlspecialchars($customer->name)."</b>\n"
            ."• HP/WA: <code>{$customer->phone}</code>\n"
            ."• Status: <b>{$statusBadge}</b>\n\n"
            ."📦 <b>Layanan Internet:</b>\n"
            .'• Paket: <b>'.($customer->package?->name ?? '-')."</b>\n"
            .'• Kecepatan: <b>'.($customer->package?->speed_rate ?? '-')."</b>\n"
            .'• Tarif: <b>Rp '.number_format($customer->package?->price ?? 0, 0, ',', '.')."/bln</b>\n"
            .'• Router: <b>'.($customer->router?->name ?? '-')."</b>\n"
            .'• PPPoE User: <code>'.($customer->pppoe_username ?: '-')."</code>\n"
            .'• IP Address: <code>'.($customer->ip_address ?: '-')."</code>\n\n"
            ."💳 <b>Status Tagihan Terakhir:</b>\n"
            ."• {$invStatus}\n"
            .'• Alamat: <i>'.htmlspecialchars($customer->address ?: '-').'</i>';

        $keyboard = [
            [
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
            ],
        ];

        return $this->sendMessage($chatId, $text, 'HTML', $keyboard);
    }

    /**
     * Command: /tagihan
     */
    protected function cmdTagihan(int|string $chatId): array
    {
        $unpaidInvoices = Invoice::with('customer')
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $totalCount = Invoice::where('status', 'unpaid')->count();
        $totalSum = Invoice::where('status', 'unpaid')->sum('total_amount');

        if ($unpaidInvoices->isEmpty()) {
            $text = "🎉 <b>Semua tagihan telah lunas!</b>\n\nTidak ada tagihan tertunggak saat ini.";

            return $this->sendMessage($chatId, $text);
        }

        $text = "💳 <b>DAFTAR TAGIHAN BELUM LUNAS</b>\n"
            ."Total: <b>{$totalCount} Tagihan</b> | Rp <b>".number_format($totalSum, 0, ',', '.')."</b>\n\n";

        foreach ($unpaidInvoices as $idx => $inv) {
            $num = $idx + 1;
            $custName = htmlspecialchars($inv->customer?->name ?? 'Pelanggan');
            $due = $inv->due_date ? date('d-m-Y', strtotime($inv->due_date)) : '-';
            $nom = number_format($inv->total_amount, 0, ',', '.');

            $text .= "<b>{$num}. {$custName}</b>\n"
                ."   • Inv: <code>{$inv->invoice_number}</code>\n"
                ."   • Nominal: <b>Rp {$nom}</b> (Tempo: {$due})\n";
        }

        $keyboard = [
            [
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
                ['text' => '🌐 Router', 'callback_data' => 'cmd_router'],
            ],
        ];

        return $this->sendMessage($chatId, $text, 'HTML', $keyboard);
    }

    /**
     * Command: /router
     */
    protected function cmdRouter(int|string $chatId): array
    {
        $routers = Router::all();

        if ($routers->isEmpty()) {
            return $this->sendMessage($chatId, 'Belum ada Router MikroTik yang terdaftar di sistem.');
        }

        $text = "🌐 <b>STATUS ROUTER MIKROTIK TERDAFTAR</b>\n\n";

        foreach ($routers as $r) {
            $statusIcon = $r->is_active ? '🟢' : '⚪';
            $cpu = $r->cpu_load !== null ? "{$r->cpu_load}%" : '-';
            $ros = $r->ros_version ?: '-';
            $uptime = $r->uptime ?: '-';

            $text .= "{$statusIcon} <b>".htmlspecialchars($r->name)."</b>\n"
                ."   • Host: <code>{$r->host}:{$r->port}</code>\n"
                ."   • CPU: {$cpu} | ROS: {$ros}\n"
                ."   • Uptime: <i>{$uptime}</i>\n\n";
        }

        $keyboard = [
            [
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
            ],
        ];

        return $this->sendMessage($chatId, $text, 'HTML', $keyboard);
    }

    /**
     * Command: /fup
     */
    protected function cmdFup(int|string $chatId): array
    {
        $period = date('Y-m');
        $usages = CustomerFupUsage::with('customer.package')
            ->where('period', $period)
            ->where('total_bytes', '>', 0)
            ->orderBy('total_bytes', 'desc')
            ->limit(5)
            ->get();

        $fupAppliedCount = Customer::where('fup_status', 'fup_applied')->count();

        $text = "📉 <b>MONITORING KUOTA FUP ({$period})</b>\n\n"
            ."• Pelanggan Terkena Throttle FUP: <b>{$fupAppliedCount}</b> orang\n\n"
            ."<b>Top 5 Pemakaian Bandwidth Bulan Ini:</b>\n";

        if ($usages->isEmpty()) {
            $text .= "<i>Belum ada catatan pemakaian data bulan ini.</i>\n";
        } else {
            foreach ($usages as $idx => $u) {
                $num = $idx + 1;
                $cust = $u->customer;
                $name = htmlspecialchars($cust?->name ?? 'Pelanggan');
                $usageGb = round($u->total_bytes / (1024 * 1024 * 1024), 2);
                $limitGb = $cust?->fup_override_limit_gb ?: ($cust?->package?->fup_limit_gb ?? 0);
                $status = ($cust?->fup_status === 'fup_applied') ? '🔴 Throttled' : '🟢 Normal';

                $text .= "<b>{$num}. {$name}</b> ({$status})\n"
                    ."   • Kuota: <b>{$usageGb} GB</b>".($limitGb > 0 ? " / {$limitGb} GB" : '')."\n";
            }
        }

        $keyboard = [
            [
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
            ],
        ];

        return $this->sendMessage($chatId, $text, 'HTML', $keyboard);
    }

    /**
     * Default response for unknown commands.
     */
    protected function cmdDefault(int|string $chatId, string $text): array
    {
        $reply = 'Perintah <code>'.htmlspecialchars($text)."</code> tidak dikenali.\n\n"
            .'Ketik <b>/help</b> atau <b>/bantuan</b> untuk melihat daftar perintah yang tersedia.';

        $keyboard = [
            [
                ['text' => '❓ Bantuan Perintah', 'callback_data' => 'cmd_help'],
                ['text' => '📊 Status ISP', 'callback_data' => 'cmd_status'],
            ],
        ];

        return $this->sendMessage($chatId, $reply, 'HTML', $keyboard);
    }
}
