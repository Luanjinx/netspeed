<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\IspSetting;
use App\Models\PushNotification;
use App\Services\Notification\OneSignalService;
use App\Services\Notification\WhatsAppService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
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

class NotificationManager extends Page implements HasForms
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    use InteractsWithForms;
    use WithPagination;

    protected static ?string $slug = 'notifications';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Notifikasi';

    protected static ?string $navigationLabel = 'Broadcast';

    protected static ?string $title = 'Pusat Pengiriman Notifikasi & Broadcast';

    protected ?string $subheading = 'Siarkan pesan pengumuman & informasi layanan ke Aplikasi Mobile (Push Notifikasi) dan WhatsApp Pelanggan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.notification-manager';

    public ?array $data = [];

    public array $connectionInfo = [];

    public bool $isConnected = false;

    public function mount(): void
    {
        $this->form->fill([
            // Form 1: Channels & Send default values
            'channels' => ['mobile', 'whatsapp'],
            'send_title' => '📢 Pengumuman Layanan ISP',
            'send_message' => '',
            'send_target_type' => 'all',
            'send_customer_id' => null,
            'send_action_url' => '',
            'send_image_url' => '',

            // Form 2: Settings
            'onesignal_enabled' => IspSetting::get('onesignal_enabled', '1') === '1',
            'onesignal_app_id' => IspSetting::get('onesignal_app_id', ''),
            'onesignal_rest_api_key' => IspSetting::get('onesignal_rest_api_key', ''),
            'onesignal_notify_invoice' => IspSetting::get('onesignal_notify_invoice', '1') === '1',
            'onesignal_notify_payment' => IspSetting::get('onesignal_notify_payment', '1') === '1',
            'onesignal_notify_ticket' => IspSetting::get('onesignal_notify_ticket', '1') === '1',
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
                Tabs::make('Notification Hub')
                    ->tabs([
                        // TAB 1: KIRIM NOTIFIKASI
                        Tab::make('Kirim Notifikasi & Broadcast')
                            ->icon('heroicon-o-paper-airplane')
                            ->schema([
                                Section::make('Siarkan Notifikasi ke Aplikasi Mobile & WhatsApp')
                                    ->description('Kirim pesan pengumuman, tagihan, atau promo serentak ke smartphone pelanggan.')
                                    ->schema([
                                        CheckboxList::make('channels')
                                            ->label('Pilih Saluran / Kanal Pengiriman')
                                            ->options([
                                                'mobile' => '📱 Aplikasi Mobile (OneSignal Push Notification)',
                                                'whatsapp' => '💬 WhatsApp Pelanggan (Gateway WhatsApp)',
                                            ])
                                            ->descriptions([
                                                'mobile' => 'Muncul sebagai banner pop-up notifikasi di HP & STB pelanggan.',
                                                'whatsapp' => 'Pesan terkirim ke obrolan WhatsApp nomor HP pelanggan.',
                                            ])
                                            ->default(['mobile', 'whatsapp'])
                                            ->columns(2)
                                            ->required()
                                            ->helperText('Pilih salah satu atau centang keduanya untuk mengirim ke multi-kanal sekaligus.'),

                                        Grid::make(2)->schema([
                                            TextInput::make('send_title')
                                                ->label('Judul Notifikasi (Title)')
                                                ->placeholder('cth: Pemberitahuan Maintenance Jaringan')
                                                ->required(),

                                            Select::make('send_target_type')
                                                ->label('Target Penerima')
                                                ->options([
                                                    'all' => '📢 Semua Pelanggan (Broadcast Massal)',
                                                    'customer' => '👤 Pelanggan Spesifik Tertentu',
                                                    'active_customers' => '🟢 Pelanggan Status Aktif Saja',
                                                    'isolated_customers' => '🔴 Pelanggan Status Isolir (Peringatan Bayar)',
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
                                            ->rows(4)
                                            ->required(),

                                        Grid::make(2)->schema([
                                            TextInput::make('send_action_url')
                                                ->label('Tautan / Action URL saat Notifikasi Diklik (Opsional)')
                                                ->placeholder('cth: /invoices atau https://isp.com/promo')
                                                ->helperText('Tautan web atau deep link aplikasi pelanggan.'),

                                            TextInput::make('send_image_url')
                                                ->label('URL Gambar Banner Notifikasi (Opsional)')
                                                ->placeholder('cth: https://domain.com/banner-promo.jpg')
                                                ->helperText('Gambar banner besar pada notifikasi HP Android/iOS.'),
                                        ]),
                                    ]),
                            ]),

                        // TAB 2: PENGATURAN KREDENSIAL ONESIGNAL
                        Tab::make('Pengaturan & Otomasi OneSignal')
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
                                                ->helperText('Salin dari OneSignal Console'),

                                            TextInput::make('onesignal_rest_api_key')
                                                ->label('OneSignal REST API Key')
                                                ->placeholder('Masukkan REST API Key...')
                                                ->password()
                                                ->revealable()
                                                ->helperText('Kunci rahasia untuk otorisasi pengiriman notifikasi dari backend'),
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

        $channels = $formData['channels'] ?? ['mobile'];
        $sendToMobile = in_array('mobile', $channels);
        $sendToWhatsApp = in_array('whatsapp', $channels);

        if (! $sendToMobile && ! $sendToWhatsApp) {
            Notification::make()
                ->title('Pilih Saluran Pengiriman')
                ->body('Pilih minimal satu saluran pengiriman: Aplikasi Mobile atau WhatsApp Pelanggan.')
                ->warning()
                ->send();

            return;
        }

        $targetType = $formData['send_target_type'] ?? 'all';
        $title = trim($formData['send_title'] ?? 'Pengumuman Layanan ISP');
        $message = trim($formData['send_message'] ?? '');
        $actionUrl = $formData['send_action_url'] ?: null;
        $imageUrl = $formData['send_image_url'] ?: null;

        $results = [];
        $totalSent = 0;
        $oneSignalId = null;

        // 1. Kirim via OneSignal (Aplikasi Mobile)
        $mobileFailed = false;
        if ($sendToMobile) {
            $service = app(OneSignalService::class);
            if ($service->isEnabled()) {
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
                    if ($customer) {
                        $params['target_label'] = "{$customer->name} ({$customer->customer_number})";
                        $params['external_ids'] = ["customer_{$customer->id}"];
                        if (! empty($customer->onesignal_id)) {
                            $params['subscription_ids'] = [$customer->onesignal_id];
                        }
                    }
                }

                $res = $service->sendPushNotification($params);
                if (! empty($res['success'])) {
                    $results[] = 'Push Mobile: '.($res['message'] ?? 'Sukses');
                    $oneSignalId = $res['notification_id'] ?? null;
                    $totalSent += max(1, (int) ($res['recipients'] ?? 1));
                } else {
                    $mobileFailed = true;
                    $results[] = 'Push Mobile: '.($res['message'] ?? 'Gagal');
                }
            } else {
                $mobileFailed = true;
                $results[] = 'Push Mobile: Dilewati (OneSignal belum dikonfigurasi)';
            }
        }

        // 2. Kirim via WhatsApp Gateway
        if ($sendToWhatsApp) {
            $waService = app(WhatsAppService::class);
            $waMessage = "*{$title}*\n\n{$message}";
            if ($actionUrl) {
                $waMessage .= "\n\nTautan: {$actionUrl}";
            }

            $waCount = 0;
            if ($targetType === 'customer') {
                $customer = Customer::find($formData['send_customer_id'] ?? null);
                if ($customer && filled($customer->phone)) {
                    $resWa = $waService->sendMessage($customer->phone, $waMessage);
                    if (! empty($resWa['success'])) {
                        $waCount++;
                    }
                }
            } else {
                $query = Customer::query()->whereNotNull('phone')->where('phone', '!=', '');
                if ($targetType === 'active_customers') {
                    $query->where('status', 'active');
                } elseif ($targetType === 'isolated_customers') {
                    $query->where('status', 'isolated');
                }

                $customers = $query->limit(500)->get();
                foreach ($customers as $c) {
                    if (filled($c->phone)) {
                        $resWa = $waService->sendMessage($c->phone, $waMessage);
                        if (! empty($resWa['success'])) {
                            $waCount++;
                        }
                    }
                }
            }

            $results[] = "WhatsApp: Terkirim ke {$waCount} nomor";
            $totalSent += $waCount;
        }

        // 3. Simpan ke Riwayat Notifikasi
        $channelLabels = [];
        if ($sendToMobile) {
            $channelLabels[] = 'Mobile';
        }
        if ($sendToWhatsApp) {
            $channelLabels[] = 'WhatsApp';
        }
        $channelText = implode(' & ', $channelLabels);

        $targetCustomer = isset($formData['send_customer_id']) ? Customer::find($formData['send_customer_id']) : null;
        $targetLabel = match ($targetType) {
            'customer' => $targetCustomer ? "{$targetCustomer->name} [{$channelText}]" : "Pelanggan [{$channelText}]",
            'active_customers' => "Pelanggan Aktif [{$channelText}]",
            'isolated_customers' => "Pelanggan Isolir [{$channelText}]",
            default => "Semua Pelanggan [{$channelText}]",
        };

        PushNotification::create([
            'title' => $title,
            'message' => $message,
            'target_type' => $targetType,
            'target_label' => $targetLabel,
            'image_url' => $imageUrl,
            'action_url' => $actionUrl,
            'status' => $totalSent > 0 ? 'sent' : 'failed',
            'recipients_count' => $totalSent,
            'onesignal_id' => $oneSignalId,
            'response_summary' => implode(' | ', $results),
            'data_payload' => [
                'channels' => $channels,
                'results' => $results,
            ],
        ]);

        $notif = Notification::make();
        if ($totalSent > 0 && ! $mobileFailed) {
            $notif
                ->title('🚀 Notifikasi Berhasil Disiarkan!')
                ->body(implode(' | ', $results))
                ->success()
                ->send();

            // Reset message input
            $this->data['send_message'] = '';
        } elseif ($totalSent > 0 && $mobileFailed) {
            $notif
                ->title('⚠️ Notifikasi Terkirim Sebagian')
                ->body(implode(' | ', $results))
                ->warning()
                ->send();
        } else {
            $notif
                ->title('❌ Gagal Menyiarkan Notifikasi')
                ->body(implode(' | ', $results))
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

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/notifications') => 'Notifikasi & Broadcast',
            'Kirim Notifikasi',
        ];
    }
}
