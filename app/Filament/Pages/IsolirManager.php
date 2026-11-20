<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class IsolirManager extends Page
{
    protected static ?string $slug = 'isolir-manager';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Jaringan';

    protected static ?string $navigationLabel = 'Redirect Isolir & Captive Portal';

    protected static ?string $title = 'Redirect Isolir & Captive Portal MikroTik (Metode 1)';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.isolir-manager';

    public ?int $selectedRouterId = null;

    public string $gatewayIp = '10.10.10.1';

    public string $networkIp = '10.10.10.0';

    public string $poolRange = '10.10.10.2-10.10.10.254';

    public string $poolName = 'pool-isolir';

    public string $bridgeName = 'bridge-isolir';

    public string $hsServerName = 'hotspot-isolir';

    public string $hsProfName = 'hsprof-isolir';

    public string $htmlDir = 'hotspot-isolir';

    public string $dnsName = 'isolir.net';

    public string $pppProfileName = 'PROFIL-ISOLIR';

    public string $companyName = '';

    public string $companyPhone = '';

    public string $bankName = 'BCA / Mandiri / BRI / QRIS';

    public string $bankAccount = '';

    public string $generatedScript = '';

    public array $setupSteps = [];

    public string $activeTab = 'setup';

    public function mount(): void
    {
        $defaultRouter = Router::where('is_active', true)->first();
        if ($defaultRouter) {
            $this->selectedRouterId = $defaultRouter->id;
        }

        $this->companyName = IspSetting::get('company_name', 'NetSpeed ISP Broadband');
        $this->companyPhone = IspSetting::get('company_phone', '0812-3456-7890');
        $this->bankAccount = IspSetting::get('bank_account_info', 'BCA: 123-456-7890 a/n NetSpeed');

        $this->refreshScript();
    }

    public function updatedSelectedRouterId(): void
    {
        $this->refreshScript();
    }

    public function refreshScript(): void
    {
        $router = $this->selectedRouterId ? Router::find($this->selectedRouterId) : Router::first();
        if (! $router) {
            $this->generatedScript = '# Tidak ada router MikroTik yang terdaftar.';

            return;
        }

        $service = app(MikrotikApiService::class);
        $this->generatedScript = $service->generateIsolirSetupScript($router, [
            'gateway_ip' => $this->gatewayIp,
            'network_ip' => $this->networkIp,
            'pool_range' => $this->poolRange,
            'pool_name' => $this->poolName,
            'bridge_name' => $this->bridgeName,
            'hs_server_name' => $this->hsServerName,
            'hs_prof_name' => $this->hsProfName,
            'dns_name' => $this->dnsName,
            'html_dir' => $this->htmlDir,
            'ppp_profile_name' => $this->pppProfileName,
            'billing_url' => url('/'),
        ]);
    }

    public function applyToRouter(): void
    {
        if (! $this->selectedRouterId) {
            Notification::make()->title('Pilih Router terlebih dahulu')->warning()->send();

            return;
        }

        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            Notification::make()->title('Router tidak ditemukan')->danger()->send();

            return;
        }

        $service = app(MikrotikApiService::class);
        $result = $service->applyIsolirSetupToRouter($router, [
            'gateway_ip' => $this->gatewayIp,
            'network_ip' => $this->networkIp,
            'pool_range' => $this->poolRange,
            'pool_name' => $this->poolName,
            'bridge_name' => $this->bridgeName,
            'hs_server_name' => $this->hsServerName,
            'hs_prof_name' => $this->hsProfName,
            'dns_name' => $this->dnsName,
            'html_dir' => $this->htmlDir,
            'ppp_profile_name' => $this->pppProfileName,
            'billing_url' => url('/'),
        ]);

        if ($result['success']) {
            $this->setupSteps = $result['steps'] ?? [];
            IspSetting::set('isolir_profile', $this->pppProfileName);
            Notification::make()
                ->title('Konfigurasi Isolir Berhasil Diterapkan!')
                ->body("Router {$router->name} telah siap untuk mengalihkan pelanggan PPPoE & Hotspot isolir.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Menerapkan Konfigurasi ke Router')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function uploadHtmlToRouter(): void
    {
        if (! $this->selectedRouterId) {
            Notification::make()->title('Pilih Router terlebih dahulu')->warning()->send();

            return;
        }

        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            return;
        }

        $service = app(MikrotikApiService::class);
        $html = $service->getIsolirHtmlContent([
            'company_name' => $this->companyName,
            'company_phone' => $this->companyPhone,
            'bank_name' => $this->bankName,
            'bank_account' => $this->bankAccount,
            'billing_url' => url('/'),
        ]);

        $res = $service->uploadIsolirHtmlToRouter($router, $html);

        if ($res['success']) {
            Notification::make()
                ->title('File Template Berhasil Diunggah!')
                ->body($res['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Informasi Unggah File')
                ->body($res['message'])
                ->warning()
                ->send();
        }
    }

    public function downloadHtml(): StreamedResponse
    {
        $service = app(MikrotikApiService::class);
        $html = $service->getIsolirHtmlContent([
            'company_name' => $this->companyName,
            'company_phone' => $this->companyPhone,
            'bank_name' => $this->bankName,
            'bank_account' => $this->bankAccount,
            'billing_url' => url('/'),
        ]);

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, 'isolir.html', [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
