<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use App\Services\Notification\WhatsAppService;
use BackedEnum;
use Filament\Actions\Action;
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

class WhatsAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'whatsapp-gateway';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'API WhatsApp Gateway';

    protected static ?string $title = 'Manajemen & Integrasi WhatsApp Gateway';

    protected ?string $subheading = 'Konfigurasi server API endpoint, token rahasia, nomor pengirim, dan otomatisasi notifikasi tagihan pelanggan';

    protected string $view = 'filament.pages.whatsapp-settings';

    public ?array $data = [];

    public bool $isConfigured = false;

    public string $endpointHost = '';

    public string $activeSender = '';

    public function mount(): void
    {
        $gatewayUrl = IspSetting::get('whatsapp_gateway_url', 'https://api.fonnte.com/send');
        $apiKey = IspSetting::get('whatsapp_api_key', '');
        $sender = IspSetting::get('whatsapp_sender_number', '');

        $this->form->fill([
            'whatsapp_gateway_url' => $gatewayUrl,
            'whatsapp_api_key' => $apiKey,
            'whatsapp_sender_number' => $sender,
            'whatsapp_notify_invoice' => IspSetting::get('whatsapp_notify_invoice', '1') === '1',
            'whatsapp_notify_reminder' => IspSetting::get('whatsapp_notify_reminder', '1') === '1',
            'whatsapp_notify_receipt' => IspSetting::get('whatsapp_notify_receipt', '1') === '1',
            'whatsapp_notify_isolir' => IspSetting::get('whatsapp_notify_isolir', '1') === '1',
            'whatsapp_notify_fup' => IspSetting::get('whatsapp_notify_fup', '1') === '1',
        ]);

        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $apiKey = $this->data['whatsapp_api_key'] ?? IspSetting::get('whatsapp_api_key', '');
        $gatewayUrl = $this->data['whatsapp_gateway_url'] ?? IspSetting::get('whatsapp_gateway_url', 'https://api.fonnte.com/send');
        $sender = $this->data['whatsapp_sender_number'] ?? IspSetting::get('whatsapp_sender_number', '');

        $this->isConfigured = ! empty(trim($apiKey));
        $host = parse_url($gatewayUrl, PHP_URL_HOST);
        $this->endpointHost = $host ? (string) $host : 'api.fonnte.com';
        $this->activeSender = ! empty($sender) ? $sender : 'Default Akun Gateway';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Kredensial & Server Gateway WhatsApp')
                        ->description('Konfigurasi link endpoint dan token autentikasi API penyedia WhatsApp Gateway (Fonnte, Wablas, atau gateway kustom).')
                        ->icon('heroicon-o-key')
                        ->schema([
                            TextInput::make('whatsapp_gateway_url')
                                ->label('Link URL Gateway WhatsApp (API Endpoint)')
                                ->placeholder('cth: https://api.fonnte.com/send')
                                ->default('https://api.fonnte.com/send')
                                ->required()
                                ->helperText('Alamat API URL pengiriman pesan. Standar Fonnte: https://api.fonnte.com/send'),

                            TextInput::make('whatsapp_api_key')
                                ->label('API Key / Token WhatsApp')
                                ->placeholder('Masukkan token autentikasi rahasia')
                                ->password()
                                ->revealable()
                                ->required()
                                ->helperText('Token API dari dashboard akun penyedia WhatsApp Gateway Anda.'),

                            TextInput::make('whatsapp_sender_number')
                                ->label('Nomor WhatsApp Pengirim / Device ID (Opsional)')
                                ->placeholder('cth: 081234567890')
                                ->tel()
                                ->helperText('Isi jika gateway Anda memerlukan parameter nomor device pengirim tertentu.'),
                        ]),

                    Section::make('Otomatisasi Notifikasi WhatsApp ke Pelanggan')
                        ->description('Pilih jenis notifikasi sistem yang otomatis dikirimkan langsung ke nomor WhatsApp pelanggan.')
                        ->icon('heroicon-o-bell-alert')
                        ->schema([
                            Toggle::make('whatsapp_notify_invoice')
                                ->label('Tagihan Baru Diterbitkan')
                                ->helperText('Kirim pesan notifikasi rincian tagihan baru, nominal, dan batas waktu jatuh tempo.')
                                ->default(true),

                            Toggle::make('whatsapp_notify_reminder')
                                ->label('Pengingat Jatuh Tempo (Reminder H-3 & H-0)')
                                ->helperText('Peringatan otomatis sebelum dan pada hari jatuh tempo agar pelanggan segera membayar.')
                                ->default(true),

                            Toggle::make('whatsapp_notify_receipt')
                                ->label('Kwitansi Pembayaran Lunas')
                                ->helperText('Kirim pesan bukti pelunasan dan konfirmasi layanan aktif setelah pembayaran sukses diverifikasi.')
                                ->default(true),

                            Toggle::make('whatsapp_notify_isolir')
                                ->label('Pemberitahuan Layanan Terisolir')
                                ->helperText('Informasikan pelanggan secara otomatis saat koneksi internet dialihkan ke profil isolir.')
                                ->default(true),

                            Toggle::make('whatsapp_notify_fup')
                                ->label('Peringatan Batas FUP Tercapai')
                                ->helperText('Pemberitahuan penyesuaian kecepatan internet saat kuota FUP paket bulanan terlampaui.')
                                ->default(true),
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testWhatsApp')
                ->label('Uji Coba Kirim Pesan')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->form([
                    TextInput::make('test_phone')
                        ->label('Nomor WhatsApp Tujuan')
                        ->placeholder('cth: 081234567890')
                        ->tel()
                        ->required(),
                    TextInput::make('test_message')
                        ->label('Isi Pesan Uji Coba')
                        ->default(function () {
                            $company = IspSetting::get('company_name', 'ISP Billing');

                            return "Halo! Ini adalah pesan uji coba integrasi API WhatsApp Gateway dari *{$company}*.\n\nWaktu: ".now()->format('d M Y, H:i:s')."\nStatus: ✅ Sukses Terhubung!";
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    $phone = $data['test_phone'];
                    $msg = $data['test_message'];
                    $service = app(WhatsAppService::class);

                    $res = $service->sendMessage($phone, $msg);
                    if ($res['success'] ?? false) {
                        Notification::make()
                            ->title('WhatsApp Berhasil Terkirim!')
                            ->body("Pesan berhasil dikirim ke {$phone}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Kirim WhatsApp')
                            ->body($res['message'] ?? 'Error saat menghubungi server gateway.')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('backToSettings')
                ->label('Kembali ke Pengaturan')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(url('/admin/settings')),

            Action::make('save')
                ->label('Simpan Pengaturan WhatsApp')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        IspSetting::set('whatsapp_gateway_url', trim($state['whatsapp_gateway_url'] ?? 'https://api.fonnte.com/send'));
        IspSetting::set('whatsapp_api_key', trim($state['whatsapp_api_key'] ?? ''));
        IspSetting::set('whatsapp_sender_number', trim($state['whatsapp_sender_number'] ?? ''));

        IspSetting::set('whatsapp_notify_invoice', ! empty($state['whatsapp_notify_invoice']) ? '1' : '0');
        IspSetting::set('whatsapp_notify_reminder', ! empty($state['whatsapp_notify_reminder']) ? '1' : '0');
        IspSetting::set('whatsapp_notify_receipt', ! empty($state['whatsapp_notify_receipt']) ? '1' : '0');
        IspSetting::set('whatsapp_notify_isolir', ! empty($state['whatsapp_notify_isolir']) ? '1' : '0');
        IspSetting::set('whatsapp_notify_fup', ! empty($state['whatsapp_notify_fup']) ? '1' : '0');

        $this->refreshStatus();

        Notification::make()
            ->title('Pengaturan WhatsApp Gateway Berhasil Disimpan!')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/settings') => 'Pengaturan Sistem',
            'API WhatsApp Gateway',
        ];
    }
}
