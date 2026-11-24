<?php

namespace App\Filament\Pages\MikrotikTools;

use App\Models\Router;
use App\Services\Router\MikrotikApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class AddAdminUserTool extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected static ?string $slug = 'mikrotik-tools/add-admin-user';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Tambah Admin User MikroTik';

    protected string $view = 'filament.pages.mikrotik-tools.add-admin-user';

    // Form inputs
    public string $rosVersion = 'v7'; // 'v6' or 'v7'

    public string $username = 'admin_noc';

    public string $password = '';

    public string $group = 'full';

    public string $allowedAddress = '';

    public string $comment = 'NetSpeed Admin User';

    public bool $disableDefaultAdmin = true;

    // Router selection & push
    public ?int $selectedRouterId = null;

    public bool $isPushing = false;

    public ?array $pushResult = null;

    public function mount(): void
    {
        $defaultRouter = Router::where('is_active', true)->first();
        if ($defaultRouter) {
            $this->selectedRouterId = $defaultRouter->id;
        }

        if (empty($this->password)) {
            $this->generatePassword();
        }
    }

    public function generatePassword(): void
    {
        // 12-char strong password with alphanumeric and symbols
        $this->password = Str::password(12, true, true, false);
    }

    public function getGeneratedScriptProperty(): string
    {
        $versionLabel = $this->rosVersion === 'v7' ? 'RouterOS v7' : 'RouterOS v6';
        $user = trim($this->username) ?: 'admin_noc';
        $pass = $this->password ?: 'password_anda';
        $grp = $this->group ?: 'full';
        $comm = trim($this->comment) ?: 'NetSpeed Admin';
        $addr = trim($this->allowedAddress);

        $addrParam = ! empty($addr) ? " address=\"{$addr}\"" : '';

        $script = "# ========================================================\n";
        $script .= "# MikroTik Script: Add Admin User ({$versionLabel})\n";
        $script .= "# Dihasilkan oleh: NetSpeed ISP Core NOC Tools\n";
        $script .= '# Tanggal: '.now()->format('Y-m-d H:i:s')."\n";
        $script .= "# ========================================================\n\n";

        $script .= "# 1. Buat / Perbarui User Admin\n";
        $script .= "/user\n";
        $script .= "add name=\"{$user}\" group={$grp} password=\"{$pass}\"{$addrParam} comment=\"{$comm}\" disabled=no\n\n";

        if ($this->disableDefaultAdmin && strtolower($user) !== 'admin') {
            $script .= "# 2. Nonaktifkan user bawaan 'admin' demi keamanan (Security Hardening)\n";
            $script .= "/user disable [find name=\"admin\"]\n";
        }

        return $script;
    }

    public function pushToMikrotik(): void
    {
        $this->validate([
            'username' => 'required|string|min:3|max:64',
            'password' => 'required|string|min:6',
            'group' => 'required|in:full,write,read',
            'selectedRouterId' => 'required|exists:routers,id',
        ], [
            'username.required' => 'Nama user admin wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'selectedRouterId.required' => 'Pilih router tujuan terlebih dahulu.',
        ]);

        $router = Router::find($this->selectedRouterId);
        if (! $router) {
            Notification::make()->title('Router tidak ditemukan.')->danger()->send();

            return;
        }

        $this->isPushing = true;
        $this->pushResult = null;

        $service = app(MikrotikApiService::class);
        $res = $service->addAdminUser($router, [
            'username' => $this->username,
            'password' => $this->password,
            'group' => $this->group,
            'allowed_address' => $this->allowedAddress,
            'comment' => $this->comment,
            'disable_default_admin' => $this->disableDefaultAdmin,
        ]);

        $this->isPushing = false;
        $this->pushResult = [
            'success' => $res['success'],
            'message' => $res['message'],
            'timestamp' => now()->format('H:i:s'),
            'router_name' => $router->name,
            'router_host' => $router->host,
        ];

        if ($res['success']) {
            Notification::make()
                ->title('User Admin Berhasil Diterapkan ke MikroTik!')
                ->body($res['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Menerapkan ke MikroTik')
                ->body($res['message'])
                ->danger()
                ->send();
        }
    }
}
