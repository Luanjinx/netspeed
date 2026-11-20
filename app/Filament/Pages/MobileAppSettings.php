<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use App\Services\Notification\OneSignalService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use UnitEnum;

class MobileAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'mobile-api';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'API Aplikasi Mobile & STB';

    protected static ?string $title = 'Integrasi API Aplikasi Mobile & STB Android';

    protected ?string $subheading = 'Konfigurasi base URL API, secret key otorisasi header mobile, Firebase Cloud Messaging (FCM), dan OneSignal push notifications';

    protected string $view = 'filament.pages.mobile-app-settings';

    public ?array $data = [];

    public string $apiBaseUrl = '';

    public string $currentApiKey = '';

    public bool $isOneSignalActive = false;

    public ?string $oneSignalAppName = null;

    public ?int $oneSignalSubscribers = null;

    public ?string $oneSignalStatusMessage = null;

    public function mount(): void
    {
        $this->apiBaseUrl = url('/api');

        $apiKey = IspSetting::get('app_api_key');
        if (empty($apiKey)) {
            $apiKey = 'isp_'.Str::random(32);
            IspSetting::set('app_api_key', $apiKey);
        }
        $this->currentApiKey = $apiKey;

        $this->form->fill([
            'app_api_base_url' => IspSetting::get('app_api_base_url', $this->apiBaseUrl),
            'app_api_key' => $apiKey,
            'fcm_server_key' => IspSetting::get('fcm_server_key', ''),
            'onesignal_app_id' => IspSetting::get('onesignal_app_id', ''),
            'onesignal_rest_api_key' => IspSetting::get('onesignal_rest_api_key', ''),
        ]);

        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $this->currentApiKey = $this->data['app_api_key'] ?? IspSetting::get('app_api_key', '');
        $oneSignalId = $this->data['onesignal_app_id'] ?? IspSetting::get('onesignal_app_id', '');
        $this->isOneSignalActive = ! empty(trim($oneSignalId));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    // Section 1: Endpoint & Secret Key
                    Section::make('Koneksi Endpoint & Otorisasi Kunci API')
                        ->description('Konfigurasi URL penghubung aplikasi mobile pelanggan (Flutter) dan STB Android ke sistem backend billing.')
                        ->icon('heroicon-o-key')
                        ->schema([
                            TextInput::make('app_api_base_url')
                                ->label('Link Base API Backend (Untuk Flutter / STB)')
                                ->default(url('/api'))
                                ->required()
                                ->helperText('Tautan ini diisikan ke variabel baseUrl di file konfigurasi aplikasi Flutter & STB Anda.')
                                ->suffixAction(
                                    Action::make('copyBaseUrlAction')
                                        ->icon('heroicon-m-clipboard-document')
                                        ->tooltip('Salin Base URL API')
                                        ->color('info')
                                        ->action(function ($livewire, $state) {
                                            $url = $state ?: url('/api');
                                            $livewire->js('navigator.clipboard.writeText('.json_encode($url).');');
                                            Notification::make()
                                                ->title('URL Berhasil Disalin!')
                                                ->body($url)
                                                ->success()
                                                ->send();
                                        })
                                ),

                            TextInput::make('app_api_key')
                                ->label('App API Secret Key (Header x-api-key)')
                                ->required()
                                ->password()
                                ->revealable()
                                ->helperText('Kunci rahasia untuk otorisasi header `x-api-key` pada setiap request API mobile pelanggan ke backend.')
                                ->suffixAction(
                                    Action::make('generateApiKeyAction')
                                        ->icon('heroicon-m-arrow-path')
                                        ->tooltip('Generate Key Baru')
                                        ->color('warning')
                                        ->requiresConfirmation()
                                        ->modalHeading('Buat Kunci API Mobile Baru?')
                                        ->modalDescription('PERINGATAN: Membuat API Key baru akan membatalkan kunci lama. Anda harus memperbarui konfigurasi di aplikasi Flutter & STB pelanggan agar tetap dapat terhubung.')
                                        ->action(function ($livewire, $set) {
                                            $newKey = 'isp_'.Str::random(32);
                                            IspSetting::set('app_api_key', $newKey);
                                            $set('app_api_key', $newKey);
                                            $livewire->currentApiKey = $newKey;

                                            Notification::make()
                                                ->title('API Key Baru Berhasil Dibuat!')
                                                ->body("Kunci: {$newKey}")
                                                ->success()
                                                ->duration(10000)
                                                ->send();
                                        })
                                ),
                        ]),

                    // Section 2: Push Notifications (OneSignal & FCM)
                    Section::make('Notifikasi Push Aplikasi (OneSignal & Firebase FCM)')
                        ->description('Layanan pengiriman pesan push broadcast & alert tagihan langsung ke layar HP / STB pelanggan.')
                        ->icon('heroicon-o-bell-alert')
                        ->schema([
                            TextInput::make('onesignal_app_id')
                                ->label('OneSignal App ID')
                                ->placeholder('cth: 3fe828b4-3717-4860-bc04-20512f4dd1be')
                                ->helperText('Dapatkan dari OneSignal Dashboard > App Settings > Keys & IDs.')
                                ->suffixAction(
                                    Action::make('testOneSignalAppId')
                                        ->icon('heroicon-m-signal')
                                        ->tooltip('Uji Koneksi OneSignal')
                                        ->color('success')
                                        ->action(function ($livewire, $get) {
                                            $livewire->runOneSignalTest(
                                                trim((string) $get('onesignal_app_id')),
                                                trim((string) $get('onesignal_rest_api_key'))
                                            );
                                        })
                                ),

                            TextInput::make('onesignal_rest_api_key')
                                ->label('OneSignal REST API Key')
                                ->placeholder('Kunci autentikasi server OneSignal')
                                ->password()
                                ->revealable()
                                ->helperText('Kunci rahasia untuk trigger broadcast notifikasi dari backend.')
                                ->suffixAction(
                                    Action::make('testOneSignalApiKey')
                                        ->icon('heroicon-m-signal')
                                        ->tooltip('Uji Koneksi OneSignal')
                                        ->color('success')
                                        ->action(function ($livewire, $get) {
                                            $livewire->runOneSignalTest(
                                                trim((string) $get('onesignal_app_id')),
                                                trim((string) $get('onesignal_rest_api_key'))
                                            );
                                        })
                                ),

                            TextInput::make('fcm_server_key')
                                ->label('Firebase Cloud Messaging (FCM) Server Key')
                                ->placeholder('Server Key Firebase')
                                ->password()
                                ->revealable()
                                ->helperText('Dapatkan dari Firebase Console > Project Settings > Cloud Messaging (Legacy).'),
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function generateKeyAction(): Action
    {
        return Action::make('generateKey')
            ->label('Buat API Key Baru')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Buat Kunci API Mobile Baru?')
            ->modalDescription('PERINGATAN: Membuat API Key baru akan membatalkan kunci lama. Anda harus memperbarui konfigurasi di aplikasi Flutter & STB pelanggan agar tetap dapat terhubung.')
            ->action(function () {
                $this->generateNewApiKey();
            });
    }

    public function generateNewApiKey(): void
    {
        $newKey = 'isp_'.Str::random(32);
        IspSetting::set('app_api_key', $newKey);
        $this->currentApiKey = $newKey;

        $this->data['app_api_key'] = $newKey;
        $this->form->fill([
            'app_api_base_url' => $this->data['app_api_base_url'] ?? IspSetting::get('app_api_base_url', url('/api')),
            'app_api_key' => $newKey,
            'fcm_server_key' => $this->data['fcm_server_key'] ?? IspSetting::get('fcm_server_key', ''),
            'onesignal_app_id' => $this->data['onesignal_app_id'] ?? IspSetting::get('onesignal_app_id', ''),
            'onesignal_rest_api_key' => $this->data['onesignal_rest_api_key'] ?? IspSetting::get('onesignal_rest_api_key', ''),
        ]);

        Notification::make()
            ->title('API Key Baru Berhasil Dibuat!')
            ->body("Kunci baru: {$newKey}")
            ->success()
            ->duration(10000)
            ->send();
    }

    public function copyBaseUrl(): void
    {
        Notification::make()
            ->title('URL Disalin!')
            ->body('Base URL API Backend telah disalin ke clipboard.')
            ->success()
            ->send();
    }

    public function runOneSignalTest(?string $appId = null, ?string $apiKey = null): void
    {
        $appId = $appId ?: trim((string) ($this->data['onesignal_app_id'] ?? IspSetting::get('onesignal_app_id', '')));
        $apiKey = $apiKey ?: trim((string) ($this->data['onesignal_rest_api_key'] ?? IspSetting::get('onesignal_rest_api_key', '')));

        if (empty($appId) || empty($apiKey)) {
            $this->isOneSignalActive = false;
            $this->oneSignalStatusMessage = 'App ID & REST API Key belum diisi';

            Notification::make()
                ->title('OneSignal Belum Dikonfigurasi!')
                ->body('Silakan isi OneSignal App ID dan REST API Key pada form terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        try {
            $service = app(OneSignalService::class);
            $res = $service->testConnection($appId, $apiKey);

            if (! empty($res['success'])) {
                $this->isOneSignalActive = true;
                $this->oneSignalAppName = $res['app_name'] ?? 'OneSignal App';
                $this->oneSignalSubscribers = $res['players_count'] ?? 0;
                $this->oneSignalStatusMessage = "Terkoneksi ke {$this->oneSignalAppName}";

                Notification::make()
                    ->title('Koneksi OneSignal Sukses!')
                    ->body("Terhubung ke {$this->oneSignalAppName} ({$this->oneSignalSubscribers} Subscriber aktif).")
                    ->success()
                    ->send();
            } else {
                $this->isOneSignalActive = false;
                $this->oneSignalStatusMessage = $res['message'] ?? 'Koneksi gagal';

                Notification::make()
                    ->title('Koneksi OneSignal Gagal')
                    ->body($res['message'] ?? 'Tidak dapat menghubungi server OneSignal.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->isOneSignalActive = false;
            $this->oneSignalStatusMessage = $e->getMessage();

            Notification::make()
                ->title('Error Uji Koneksi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testOneSignalConnection(): void
    {
        $this->runOneSignalTest();
    }

    public function save(): void
    {
        $state = $this->form->getState();

        IspSetting::set('app_api_base_url', trim($state['app_api_base_url'] ?? url('/api')));
        IspSetting::set('app_api_key', trim($state['app_api_key'] ?? ''));
        IspSetting::set('onesignal_app_id', trim($state['onesignal_app_id'] ?? ''));
        IspSetting::set('onesignal_rest_api_key', trim($state['onesignal_rest_api_key'] ?? ''));
        IspSetting::set('fcm_server_key', trim($state['fcm_server_key'] ?? ''));

        $this->refreshStatus();

        Notification::make()
            ->title('Pengaturan API Mobile & Push Notifikasi Berhasil Disimpan!')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/settings') => 'Pengaturan Sistem',
            'API Aplikasi Mobile & STB Android',
        ];
    }
}
