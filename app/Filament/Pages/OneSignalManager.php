<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\IspSetting;
use App\Models\PushNotification;
use App\Services\Notification\OneSignalService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Livewire\WithPagination;
use UnitEnum;

class OneSignalManager extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static ?string $slug = 'onesignal';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Push Notifikasi OneSignal';

    protected static ?string $title = 'Manajemen Push Notifikasi OneSignal';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.onesignal-manager';

    public ?array $data = [];

    public array $connectionInfo = [];

    public bool $isConnected = false;

    public function mount(): void
    {
        $this->form->fill([
            // Form 1: Settings
            'onesignal_enabled' => IspSetting::get('onesignal_enabled', '1') === '1',
            'onesignal_app_id' => IspSetting::get('onesignal_app_id', ''),
            'onesignal_rest_api_key' => IspSetting::get('onesignal_rest_api_key', ''),
            'onesignal_notify_invoice' => IspSetting::get('onesignal_notify_invoice', '1') === '1',
            'onesignal_notify_payment' => IspSetting::get('onesignal_notify_payment', '1') === '1',
            'onesignal_notify_ticket' => IspSetting::get('onesignal_notify_ticket', '1') === '1',

            // Form 2: Send Form default values
            'send_title' => '📢 Pengumuman Layanan ISP',
            'send_message' => '',
            'send_target_type' => 'all',
            'send_customer_id' => null,
            'send_action_url' => '',
            'send_image_url' => '',
        ]);

        $this->refreshConnectionStatus();
    }

    public function refreshConnectionStatus(): void
    {
        $service = app(OneSignalService::class);
        $appId = $this->data['onesignal_app_id'] ?? $service->getAppId();
        $apiKey = $this->data['onesignal_rest_api_key'] ?? $service->getRestApiKey();

        if (empty($appId) || empty($apiKey)) {
            $this->isConnected = false;
            $this->connectionInfo = ['error' => 'App ID atau REST API Key OneSignal belum diisi.'];

            return;
        }

        $res = $service->testConnection($appId, $apiKey);
        if ($res['success']) {
            $this->isConnected = true;
            $this->connectionInfo = $res;
        } else {
            $this->isConnected = false;
            $this->connectionInfo = ['error' => $res['message']];
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('OneSignal Hub')
                    ->tabs([
                        // TAB 1: KIRIM NOTIFIKASI
                        Tab::make('Kirim Push Notifikasi')
                            ->icon('heroicon-o-paper-airplane')
                            ->schema([
                                Section::make('Buat & Siarkan Push Notifikasi ke Aplikasi Mobile')
                                    ->description('Kirim notifikasi langsung ke smartphone pelanggan atau teknisi melalui jaringan OneSignal.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('send_title')
                                                ->label('Judul Notifikasi (Title)')
                                                ->placeholder('cth: Pemberitahuan Maintenance Jaringan')
                                                ->required(),

                                            Select::make('send_target_type')
                                                ->label('Target Penerima')
                                                ->options([
                                                    'all' => '📢 Semua Perangkat Pelanggan (Broadcast)',
                                                    'customer' => '👤 Pelanggan Spesifik Tertentu',
                                                    'active_customers' => '🟢 Pelanggan Status Aktif Saja',
                                                    'isolated_customers' => '🔴 Pelanggan Status Isolir (Pengingat Bayar)',
                                                ])
                                                ->default('all')
                                                ->live()
                                                ->required(),
                                        ]),

                                        Select::make('send_customer_id')
                                            ->label('Pilih Pelanggan Tujuan')
                                            ->placeholder('Cari nama / nomor pelanggan...')
                                            ->options(Customer::pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn ($get) => $get('send_target_type') === 'customer')
                                            ->required(fn ($get) => $get('send_target_type') === 'customer'),

                                        Textarea::make('send_message')
                                            ->label('Isi Pesan Notifikasi (Message / Body)')
                                            ->placeholder('Tuliskan detail pengumuman atau instruksi...')
                                            ->rows(3)
                                            ->required(),

                                        Grid::make(2)->schema([
                                            TextInput::make('send_action_url')
                                                ->label('Tautan / Action URL saat Notifikasi Diklik (Opsional)')
                                                ->placeholder('cth: /invoices atau https://isp.com/promo')
                                                ->helperText('Dapat berupa deep link halaman aplikasi mobile atau tautan web.'),

                                            TextInput::make('send_image_url')
                                                ->label('URL Gambar Banner Notifikasi (Opsional)')
                                                ->placeholder('cth: https://domain.com/banner-promo.jpg')
                                                ->helperText('Gambar akan ditampilkan sebagai banner besar di notifikasi HP.'),
                                        ]),
                                    ]),
                            ]),

                        // TAB 2: PENGATURAN KREDENSIAL ONESIGNAL
                        Tab::make('Pengaturan & Otomasi')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Kredensial API OneSignal')
                                    ->description('Dapatkan App ID dan REST API Key dari OneSignal Dashboard > App Settings > Keys & IDs.')
                                    ->schema([
                                        Toggle::make('onesignal_enabled')
                                            ->label('Aktifkan Layanan OneSignal Push Notification')
                                            ->helperText('Jika dinonaktifkan, seluruh push notifikasi manual maupun otomatis tidak akan dikirim.')
                                            ->default(true),

                                        Grid::make(2)->schema([
                                            TextInput::make('onesignal_app_id')
                                                ->label('OneSignal App ID')
                                                ->placeholder('cth: 3fa85f64-5717-4562-b3fc-2c963f66afa6')
                                                ->helperText('Salin dari OneSignal Console')
                                                ->required(),

                                            TextInput::make('onesignal_rest_api_key')
                                                ->label('OneSignal REST API Key')
                                                ->placeholder('Masukkan REST API Key...')
                                                ->password()
                                                ->revealable()
                                                ->helperText('Kunci rahasia untuk otorisasi pengiriman notifikasi dari backend')
                                                ->required(),
                                        ]),
                                    ]),

                                Section::make('Otomasi Notifikasi Sistem ke Aplikasi Pelanggan')
                                    ->description('Sistem akan otomatis mengirimkan push notifikasi ke HP pelanggan pada saat kejadian berikut terjadi:')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Toggle::make('onesignal_notify_invoice')
                                                ->label('Notifikasi Tagihan Baru')
                                                ->helperText('Otomatis kirim saat invoice bulanan terbit.')
                                                ->default(true),

                                            Toggle::make('onesignal_notify_payment')
                                                ->label('Konfirmasi Pembayaran Lunas')
                                                ->helperText('Otomatis kirim saat pembayaran QRIS/Transfer terverifikasi.')
                                                ->default(true),

                                            Toggle::make('onesignal_notify_ticket')
                                                ->label('Update Tiket Gangguan')
                                                ->helperText('Otomatis kirim saat ada balasan atau perubahan status tiket.')
                                                ->default(true),
                                        ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Uji Koneksi OneSignal')
                ->icon('heroicon-o-bolt')
                ->color('info')
                ->action(function () {
                    $this->refreshConnectionStatus();
                    if ($this->isConnected) {
                        $name = $this->connectionInfo['app_name'] ?? 'OneSignal';
                        $players = $this->connectionInfo['players_count'] ?? 0;
                        Notification::make()
                            ->title('✅ OneSignal Terhubung!')
                            ->body("Aplikasi: {$name} | Total Perangkat Terdaftar: {$players}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('❌ Gagal Terhubung ke OneSignal')
                            ->body($this->connectionInfo['error'] ?? 'Periksa App ID dan REST API Key Anda.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('clearHistory')
                ->label('Hapus Riwayat')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Seluruh Riwayat Notifikasi')
                ->modalDescription('Apakah Anda yakin ingin menghapus seluruh log riwayat pengiriman notifikasi dari database?')
                ->action(function () {
                    PushNotification::truncate();
                    Notification::make()->title('Riwayat notifikasi berhasil dibersihkan')->success()->send();
                }),
        ];
    }

    public function sendNotification(): void
    {
        $this->form->validate();
        $formData = $this->form->getState();

        $service = app(OneSignalService::class);
        if (! $service->isEnabled()) {
            Notification::make()
                ->title('Layanan OneSignal Belum Aktif')
                ->body('Pastikan Anda sudah menyimpan OneSignal App ID & REST API Key yang valid terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $targetType = $formData['send_target_type'] ?? 'all';
        $title = $formData['send_title'];
        $message = $formData['send_message'];
        $actionUrl = $formData['send_action_url'] ?: null;
        $imageUrl = $formData['send_image_url'] ?: null;

        $params = [
            'title' => $title,
            'message' => $message,
            'target_type' => $targetType,
            'action_url' => $actionUrl,
            'image_url' => $imageUrl,
            'data' => [
                'sender' => 'admin_panel',
                'sent_at' => now()->toIso8601String(),
            ],
        ];

        if ($targetType === 'customer') {
            $customer = Customer::find($formData['send_customer_id'] ?? null);
            if (! $customer) {
                Notification::make()->title('Pelanggan tidak ditemukan')->danger()->send();

                return;
            }
            $params['target_label'] = "{$customer->name} ({$customer->customer_number})";
            $params['external_ids'] = ["customer_{$customer->id}"];
        }

        $res = $service->sendPushNotification($params);

        if ($res['success']) {
            Notification::make()
                ->title('🚀 Push Notifikasi Berhasil Dikirim!')
                ->body($res['message'])
                ->success()
                ->send();

            // Clear message form
            $this->data['send_message'] = '';
        } else {
            Notification::make()
                ->title('Gagal Mengirim Push Notifikasi')
                ->body($res['message'])
                ->danger()
                ->send();
        }
    }

    public function saveSettings(): void
    {
        $formData = $this->form->getState();

        IspSetting::set('onesignal_enabled', ($formData['onesignal_enabled'] ?? true) ? '1' : '0');
        IspSetting::set('onesignal_app_id', trim($formData['onesignal_app_id'] ?? ''));
        IspSetting::set('onesignal_rest_api_key', trim($formData['onesignal_rest_api_key'] ?? ''));
        IspSetting::set('onesignal_notify_invoice', ($formData['onesignal_notify_invoice'] ?? true) ? '1' : '0');
        IspSetting::set('onesignal_notify_payment', ($formData['onesignal_notify_payment'] ?? true) ? '1' : '0');
        IspSetting::set('onesignal_notify_ticket', ($formData['onesignal_notify_ticket'] ?? true) ? '1' : '0');

        $this->refreshConnectionStatus();

        Notification::make()
            ->title('Pengaturan OneSignal Berhasil Disimpan!')
            ->success()
            ->send();
    }

    public function resendNotification(int $id): void
    {
        $log = PushNotification::find($id);
        if (! $log) {
            Notification::make()->title('Log notifikasi tidak ditemukan')->danger()->send();

            return;
        }

        $service = app(OneSignalService::class);
        $res = $service->sendPushNotification([
            'title' => $log->title,
            'message' => $log->message,
            'target_type' => $log->target_type,
            'target_label' => $log->target_label,
            'external_ids' => $log->external_ids,
            'action_url' => $log->action_url,
            'image_url' => $log->image_url,
            'data' => $log->data_payload ?? [],
        ]);

        if ($res['success']) {
            Notification::make()->title('Notifikasi berhasil dikirim ulang')->success()->send();
        } else {
            Notification::make()->title('Gagal kirim ulang')->body($res['message'])->danger()->send();
        }
    }

    public function deleteLog(int $id): void
    {
        PushNotification::where('id', $id)->delete();
        Notification::make()->title('Log berhasil dihapus')->success()->send();
    }

    public function getNotificationsProperty()
    {
        return PushNotification::latest()->paginate(10);
    }
}
