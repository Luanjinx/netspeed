<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
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
use UnitEnum;

class MapSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'map-settings';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'API Peta & Geocoding';

    protected static ?string $title = 'Manajemen API Peta & Koordinat NOC';

    protected ?string $subheading = 'Konfigurasi Google Maps JavaScript API key, koordinat pusat kantor NOC, dan peta rute teknisi lapangan';

    protected string $view = 'filament.pages.map-settings';

    public ?array $data = [];

    public bool $hasGoogleApiKey = false;

    public string $officeCoords = '';

    public int $zoomLevel = 16;

    public function mount(): void
    {
        $office = IspSetting::getOfficeLocation();
        $apiKey = IspSetting::get('google_maps_api_key', '');
        $zoom = (int) IspSetting::get('default_zoom', '16');

        $this->form->fill([
            'google_maps_api_key' => $apiKey,
            'office_lat' => (string) ($office['lat'] ?? '-6.2087634'),
            'office_lng' => (string) ($office['lng'] ?? '106.845599'),
            'default_zoom' => $zoom,
        ]);

        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $apiKey = $this->data['google_maps_api_key'] ?? IspSetting::get('google_maps_api_key', '');
        $lat = $this->data['office_lat'] ?? IspSetting::get('office_lat', '-6.2087634');
        $lng = $this->data['office_lng'] ?? IspSetting::get('office_lng', '106.845599');
        $zoom = (int) ($this->data['default_zoom'] ?? IspSetting::get('default_zoom', '16'));

        $this->hasGoogleApiKey = ! empty(trim($apiKey));
        $this->officeCoords = "{$lat}, {$lng}";
        $this->zoomLevel = $zoom > 0 ? $zoom : 16;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    // Section 1: Google Maps Platform
                    Section::make('Kredensial Google Maps Platform (Opsional)')
                        ->description('API Key resmi dari Google Cloud Console untuk fitur satelit hybrid, geocoding alamat, dan rute navigasi teknisi.')
                        ->icon('heroicon-o-key')
                        ->schema([
                            TextInput::make('google_maps_api_key')
                                ->label('Google Maps JavaScript API Key')
                                ->placeholder('cth: AIzaSy...')
                                ->password()
                                ->revealable()
                                ->helperText('Jika kosong, sistem otomatis beralih menggunakan tile satelit gratis OpenStreetMap & Google Tile publik bawaan tanpa biaya kuota API.'),
                        ]),

                    // Section 2: Titik Koordinat Kantor Pusat NOC
                    Section::make('Koordinat Titik Kantor Pusat / NOC (Pusat Peta)')
                        ->description('Titik acuan utama saat peta admin (/admin/network-map) dan peta teknisi lapangan (/tech/map) pertama kali dimuat.')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('office_lat')
                                    ->label('Latitude Kantor Pusat')
                                    ->placeholder('cth: -6.2087634')
                                    ->required()
                                    ->helperText('Koordinat lintang (Latitude) kantor ISP Anda.'),

                                TextInput::make('office_lng')
                                    ->label('Longitude Kantor Pusat')
                                    ->placeholder('cth: 106.845599')
                                    ->required()
                                    ->helperText('Koordinat bujur (Longitude) kantor ISP Anda.'),
                            ]),

                            TextInput::make('default_zoom')
                                ->label('Level Zoom Default Peta (Skala 1 - 20)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(20)
                                ->default(16)
                                ->required()
                                ->helperText('Tingkat perbesaran awal peta (Rekomendasi level zoom NOC: 16).'),
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openAdminMap')
                ->label('Buka Peta Jaringan Admin')
                ->icon('heroicon-o-map')
                ->color('success')
                ->url(url('/admin/network-map')),

            Action::make('openTechMap')
                ->label('Buka Peta Teknisi')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('info')
                ->url(url('/tech/map'))
                ->openUrlInNewTab(),

            Action::make('backToSettings')
                ->label('Kembali ke Pengaturan')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(url('/admin/settings')),

            Action::make('save')
                ->label('Simpan Pengaturan Peta')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        IspSetting::set('google_maps_api_key', trim($state['google_maps_api_key'] ?? ''));
        IspSetting::set('office_lat', trim($state['office_lat'] ?? '-6.2087634'));
        IspSetting::set('office_lng', trim($state['office_lng'] ?? '106.845599'));
        IspSetting::set('default_zoom', (string) ($state['default_zoom'] ?? 16));

        // Sync to config/settings.json
        $jsonPath = config_path('settings.json');
        $json = [
            'office_name' => IspSetting::get('company_name', 'Kantor Pusat ISP'),
            'office_lat' => (float) ($state['office_lat'] ?? -6.2087634),
            'office_lng' => (float) ($state['office_lng'] ?? 106.845599),
            'default_zoom' => (int) ($state['default_zoom'] ?? 16),
        ];
        @file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->refreshStatus();

        Notification::make()
            ->title('Pengaturan Peta & Titik Koordinat Berhasil Disimpan!')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/settings') => 'Pengaturan Sistem',
            'API Peta & Geocoding',
        ];
    }
}
