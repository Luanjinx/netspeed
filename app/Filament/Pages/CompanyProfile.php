<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use BackedEnum;
use Exception;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class CompanyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'company-profile';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'General & Profil ISP';

    protected static ?string $title = 'Pengaturan General & Profil ISP';

    protected ?string $subheading = 'Nama penyedia layanan yang tercetak pada invoice tagihan, struk kasir, kontak layanan CS WhatsApp, dan titik koordinat peta kantor pusat NOC';

    protected string $view = 'filament.pages.company-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $office = [];
        $settingsFile = config_path('settings.json');
        if (file_exists($settingsFile)) {
            $office = json_decode((string) file_get_contents($settingsFile), true) ?? [];
        }

        $this->form->fill([
            'company_name' => IspSetting::get('company_name', 'NETSPEED WI-FI BROADBAND'),
            'company_phone' => IspSetting::get('company_phone', '0812-3456-7890'),
            'company_address' => IspSetting::get('company_address', 'Jl. Telekomunikasi No. 88, Jakarta'),
            'office_lat' => (string) ($office['lat'] ?? IspSetting::get('office_lat', '-6.2087634')),
            'office_lng' => (string) ($office['lng'] ?? IspSetting::get('office_lng', '106.845599')),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Identitas Perusahaan / Penyedia Layanan')
                        ->description('Informasi nama dan kontak ISP yang tercetak di invoice tagihan, struk kasir thermal, dan pesan WhatsApp')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            TextInput::make('company_name')
                                ->label('Nama Brand / Perusahaan ISP')
                                ->placeholder('cth: NETSPEED WI-FI BROADBAND')
                                ->required(),

                            TextInput::make('company_phone')
                                ->label('Nomor WhatsApp Layanan CS / Bantuan')
                                ->placeholder('cth: 0812-3456-7890')
                                ->required(),

                            Textarea::make('company_address')
                                ->label('Alamat Kantor Pusat / NOC')
                                ->placeholder('Alamat lengkap kantor penyedia internet')
                                ->rows(4),
                        ]),

                    Section::make('Koordinat Titik Kantor Pusat / NOC (Pusat Peta)')
                        ->description('Titik koordinat awal saat teknisi membuka peta lapangan (/tech/map) dan peta admin (/admin/map)')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            TextInput::make('office_lat')
                                ->label('Latitude Kantor Pusat')
                                ->placeholder('cth: -6.2087634')
                                ->helperText('Koordinat garis lintang (cth: -6.2087634)')
                                ->required(),

                            TextInput::make('office_lng')
                                ->label('Longitude Kantor Pusat')
                                ->placeholder('cth: 106.845599')
                                ->helperText('Koordinat garis bujur (cth: 106.845599)')
                                ->required(),
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        IspSetting::set('company_name', trim($state['company_name'] ?? ''));
        IspSetting::set('company_phone', trim($state['company_phone'] ?? ''));
        IspSetting::set('company_address', trim($state['company_address'] ?? ''));
        IspSetting::set('office_lat', trim($state['office_lat'] ?? ''));
        IspSetting::set('office_lng', trim($state['office_lng'] ?? ''));

        // Update settings.json
        $jsonPath = config_path('settings.json');
        $json = [
            'office_name' => trim($state['company_name'] ?? 'Kantor Pusat ISP'),
            'office_lat' => (float) ($state['office_lat'] ?? -6.2087634),
            'office_lng' => (float) ($state['office_lng'] ?? 106.845599),
            'default_zoom' => 16,
        ];
        @file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Auto-sync isolir template to active routers with latest company_phone & company_name
        try {
            $companyPhone = trim($state['company_phone'] ?? '');
            $companyName = trim($state['company_name'] ?? '');

            if ($companyPhone !== '' || $companyName !== '') {
                $routers = Router::where('is_active', true)->get();
                $apiService = app(MikrotikApiService::class);

                foreach ($routers as $router) {
                    try {
                        $client = $apiService->connect($router);
                        $apiService->uploadIsolirHtml($client, $companyPhone, $companyName);
                    } catch (Exception $routerEx) {
                        Log::warning("CompanyProfile: Failed updating isolir HTML on router {$router->name}: ".$routerEx->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('CompanyProfile: Isolir HTML auto-sync failed: '.$e->getMessage());
        }

        Notification::make()
            ->title('Profil Perusahaan & Titik Peta Berhasil Disimpan!')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/settings') => 'Pengaturan Sistem',
            'General & Profil ISP',
        ];
    }
}
