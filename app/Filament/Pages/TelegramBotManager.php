<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use App\Services\Notification\TelegramService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class TelegramBotManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'telegram-bot';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Bot Telegram';

    protected static ?string $title = 'Manajemen & Integrasi Bot Telegram';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.telegram-bot-manager';

    public ?array $data = [];

    public array $botInfo = [];

    public array $webhookInfo = [];

    public bool $isBotConnected = false;

    public function mount(): void
    {
        $this->form->fill([
            'telegram_bot_token' => IspSetting::get('telegram_bot_token', ''),
            'telegram_admin_chat_id' => IspSetting::get('telegram_admin_chat_id', ''),
            'telegram_bot_enabled' => IspSetting::get('telegram_bot_enabled', '1') === '1',
            'telegram_notify_payment' => IspSetting::get('telegram_notify_payment', '1') === '1',
            'telegram_notify_invoice' => IspSetting::get('telegram_notify_invoice', '0') === '1',
            'telegram_notify_ticket' => IspSetting::get('telegram_notify_ticket', '1') === '1',
            'telegram_notify_router' => IspSetting::get('telegram_notify_router', '1') === '1',
            'telegram_notify_fup' => IspSetting::get('telegram_notify_fup', '1') === '1',
            'webhook_url_display' => url('/api/telegram/webhook'),
        ]);

        $this->refreshBotStatus();
    }

    public function refreshBotStatus(): void
    {
        $telegram = app(TelegramService::class);
        $token = $this->data['telegram_bot_token'] ?? IspSetting::get('telegram_bot_token', '');

        if (empty($token)) {
            $this->isBotConnected = false;
            $this->botInfo = [];
            $this->webhookInfo = [];

            return;
        }

        $me = $telegram->getMe($token);
        if ($me['ok'] ?? false) {
            $this->isBotConnected = true;
            $this->botInfo = $me['result'] ?? [];
        } else {
            $this->isBotConnected = false;
            $this->botInfo = ['error' => $me['description'] ?? 'Token bot tidak valid atau tidak dapat terhubung'];
        }

        $hook = $telegram->getWebhookInfo();
        if ($hook['ok'] ?? false) {
            $this->webhookInfo = $hook['result'] ?? [];
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Kredensial & Autentikasi Bot Telegram')
                    ->description('Dapatkan token bot dari @BotFather di Telegram untuk menghubungkan sistem billing dengan akun bot Anda.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Toggle::make('telegram_bot_enabled')
                            ->label('Aktifkan Layanan Bot Telegram')
                            ->helperText('Jika dinonaktifkan, pesan notifikasi sistem tidak akan diteruskan ke Telegram.')
                            ->default(true),

                        Grid::make(2)->schema([
                            TextInput::make('telegram_bot_token')
                                ->label('Telegram Bot Token (HTTP API)')
                                ->placeholder('cth: 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ')
                                ->password()
                                ->revealable()
                                ->helperText('Dapatkan dari chat dengan @BotFather -> /newbot atau /token')
                                ->required(),

                            TextInput::make('telegram_admin_chat_id')
                                ->label('Chat ID Admin / NOC (Grup, Channel, atau Pribadi)')
                                ->placeholder('cth: 123456789 atau -100192837465')
                                ->helperText('ID penerima notifikasi otomatis. Kirim /id ke bot untuk mengetahui ID Anda, atau gunakan @userinfobot.')
                                ->required(),
                        ]),

                        TextInput::make('webhook_url_display')
                            ->label('Alamat Webhook Endpoint Aplikasi')
                            ->default(url('/api/telegram/webhook'))
                            ->readOnly()
                            ->copyable()
                            ->helperText('URL ini digunakan oleh Telegram untuk mengirim pesan balik ke aplikasi ISP. Klik tombol "Set Webhook" di atas untuk mendaftarkannya otomatis.'),
                    ]),

                Section::make('Konfigurasi Notifikasi Otomatis ke Grup / Admin')
                    ->description('Pilih jenis kejadian sistem yang ingin dikirimkan secara otomatis ke Chat ID Telegram Admin/NOC.')
                    ->icon('heroicon-o-bell-alert')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('telegram_notify_payment')
                                ->label('Notifikasi Pembayaran Lunas (QRIS / Transfer)')
                                ->helperText('Mengirim notifikasi instan saat tagihan pelanggan berhasil dibayar & PPPoE aktif.')
                                ->default(true),

                            Toggle::make('telegram_notify_ticket')
                                ->label('Notifikasi Tiket Gangguan Baru')
                                ->helperText('Pemberitahuan darurat saat pelanggan membuka tiket keluhan baru melalui aplikasi mobile.')
                                ->default(true),

                            Toggle::make('telegram_notify_router')
                                ->label('Notifikasi Router MikroTik Down / Pulih')
                                ->helperText('Peringatan saat router gateway terputus atau kembali online.')
                                ->default(true),

                            Toggle::make('telegram_notify_fup')
                                ->label('Notifikasi Kuota FUP Tercapai')
                                ->helperText('Laporan saat pelanggan PPPoE mencapai batas kuota dan kecepatannya diturunkan.')
                                ->default(true),

                            Toggle::make('telegram_notify_invoice')
                                ->label('Notifikasi Tagihan Baru Diterbitkan')
                                ->helperText('Kirim rekap tagihan bulanan baru saat invoice diterbitkan.')
                                ->default(false),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMessage')
                ->label('Test Kirim Pesan')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->form([
                    TextInput::make('test_chat_id')
                        ->label('Chat ID Tujuan')
                        ->default(fn () => $this->data['telegram_admin_chat_id'] ?? '')
                        ->placeholder('cth: 123456789 atau -100123456789')
                        ->required(),
                    Textarea::make('test_text')
                        ->label('Isi Pesan Uji Coba')
                        ->default("🚀 <b>TEST BOT TELEGRAM ISP BILLING</b>\n\nKoneksi bot Telegram dan server ISP berhasil terhubung sempurna!")
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $telegram = app(TelegramService::class);
                    $res = $telegram->sendMessage($data['test_chat_id'], $data['test_text']);

                    if ($res['ok'] ?? false) {
                        Notification::make()
                            ->title('✅ Pesan Uji Coba Terkirim!')
                            ->body('Pesan berhasil terkirim ke Telegram chat ID: '.$data['test_chat_id'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('❌ Gagal Mengirim Pesan')
                            ->body($res['description'] ?? 'Pastikan token bot valid dan pengguna sudah menekan /start di bot.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('setWebhook')
                ->label('Set Webhook Otomatis')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Daftarkan Webhook Telegram')
                ->modalDescription('Telegram akan mengirim update pesan ke: '.url('/api/telegram/webhook').'. Pastikan server dapat diakses publik (menggunakan HTTPS / domain / ngrok).')
                ->action(function () {
                    $telegram = app(TelegramService::class);
                    $res = $telegram->setWebhook();

                    if ($res['ok'] ?? false) {
                        Notification::make()
                            ->title('✅ Webhook Berhasil Didaftarkan!')
                            ->body($res['description'] ?? 'Webhook aktif.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('❌ Gagal Set Webhook')
                            ->body($res['description'] ?? 'Periksa apakah URL menggunakan HTTPS yang valid.')
                            ->danger()
                            ->send();
                    }

                    $this->refreshBotStatus();
                }),

            Action::make('deleteWebhook')
                ->label('Hapus Webhook (Mode Polling)')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Hapus Webhook Telegram?')
                ->modalDescription('Setelah webhook dihapus, Anda dapat menggunakan perintah polling konsol: php artisan telegram:bot --poll')
                ->action(function () {
                    $telegram = app(TelegramService::class);
                    $res = $telegram->deleteWebhook();

                    if ($res['ok'] ?? false) {
                        Notification::make()
                            ->title('✅ Webhook Dihapus')
                            ->body('Bot beralih ke mode polling / manual.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Hapus Webhook')
                            ->body($res['description'] ?? 'Terjadi kesalahan')
                            ->danger()
                            ->send();
                    }

                    $this->refreshBotStatus();
                }),

            Action::make('broadcast')
                ->label('Broadcast Pengumuman')
                ->icon('heroicon-o-megaphone')
                ->color('warning')
                ->form([
                    TextInput::make('bc_chat_id')
                        ->label('Target Chat ID / Grup / Channel')
                        ->default(fn () => $this->data['telegram_admin_chat_id'] ?? '')
                        ->required(),
                    Textarea::make('bc_message')
                        ->label('Pesan Broadcast (Mendukung HTML)')
                        ->placeholder('Tulis pengumuman di sini...')
                        ->rows(5)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $telegram = app(TelegramService::class);
                    $res = $telegram->sendMessage($data['bc_chat_id'], $data['bc_message']);

                    if ($res['ok'] ?? false) {
                        Notification::make()
                            ->title('📢 Broadcast Terkirim')
                            ->body('Pesan telah disiarkan ke target Telegram.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Broadcast')
                            ->body($res['description'] ?? 'Pastikan bot telah ditambahkan ke grup/channel tersebut.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('refresh')
                ->label('Segarkan Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->refreshBotStatus();
                    Notification::make()->title('Status Bot Disegarkan')->info()->send();
                }),

            Action::make('save')
                ->label('Simpan Pengaturan')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->saveSettings()),
        ];
    }

    public function saveSettings(): void
    {
        $state = $this->form->getState();

        IspSetting::set('telegram_bot_token', trim($state['telegram_bot_token'] ?? ''));
        IspSetting::set('telegram_admin_chat_id', trim($state['telegram_admin_chat_id'] ?? ''));
        IspSetting::set('telegram_bot_enabled', ! empty($state['telegram_bot_enabled']) ? '1' : '0');
        IspSetting::set('telegram_notify_payment', ! empty($state['telegram_notify_payment']) ? '1' : '0');
        IspSetting::set('telegram_notify_invoice', ! empty($state['telegram_notify_invoice']) ? '1' : '0');
        IspSetting::set('telegram_notify_ticket', ! empty($state['telegram_notify_ticket']) ? '1' : '0');
        IspSetting::set('telegram_notify_router', ! empty($state['telegram_notify_router']) ? '1' : '0');
        IspSetting::set('telegram_notify_fup', ! empty($state['telegram_notify_fup']) ? '1' : '0');

        $this->refreshBotStatus();

        Notification::make()
            ->title('✅ Pengaturan Bot Telegram Tersimpan')
            ->body('Konfigurasi bot dan aturan notifikasi telah diperbarui.')
            ->success()
            ->send();
    }
}
