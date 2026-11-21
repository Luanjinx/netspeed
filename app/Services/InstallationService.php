<?php

namespace App\Services;

use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class InstallationService
{
    /**
     * Check if application is already installed.
     */
    public static function isInstalled(): bool
    {
        if (File::exists(storage_path('installed'))) {
            return true;
        }

        // Auto-detect: if user is authenticated or users table has accounts,
        // the app is already installed. Create lock file automatically.
        try {
            if (auth()->check() || (Schema::hasTable('users') && User::count() > 0)) {
                @File::put(storage_path('installed'), json_encode([
                    'installed_at' => now()->toIso8601String(),
                    'app_version' => '1.0.0',
                    'installed_by' => 'Auto-Detected System',
                ], JSON_PRETTY_PRINT));

                return true;
            }
        } catch (Throwable) {
            // Database not ready or tables not yet migrated
        }

        return false;
    }

    /**
     * Check server requirements (PHP version and extensions).
     *
     * @return array{
     *     all_passed: bool,
     *     php_version: array{current: string, required: string, passed: bool},
     *     extensions: array<string, array{name: string, passed: bool, required: bool}>,
     *     directories: array<string, array{path: string, passed: bool, required: bool}>
     * }
     */
    public static function checkRequirements(): array
    {
        $minPhpVersion = '8.2.0';
        $currentPhp = PHP_VERSION;
        $phpPassed = version_compare($currentPhp, $minPhpVersion, '>=');

        $requiredExtensions = [
            'pdo' => 'PDO Extension',
            'pdo_mysql' => 'PDO MySQL Driver',
            'mbstring' => 'Mbstring Extension',
            'openssl' => 'OpenSSL Extension',
            'tokenizer' => 'Tokenizer Extension',
            'xml' => 'XML Extension',
            'ctype' => 'Ctype Extension',
            'json' => 'JSON Extension',
            'bcmath' => 'BCMath Extension',
            'fileinfo' => 'FileInfo Extension',
            'curl' => 'cURL Extension',
            'zip' => 'ZIP Extension',
        ];

        $extensionResults = [];
        $extensionsPassed = true;

        foreach ($requiredExtensions as $ext => $label) {
            $isLoaded = extension_loaded($ext);
            $extensionResults[$ext] = [
                'name' => $label,
                'passed' => $isLoaded,
                'required' => true,
            ];
            if (! $isLoaded) {
                $extensionsPassed = false;
            }
        }

        $checkDirs = [
            'storage' => storage_path(),
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $dirResults = [];
        $dirsPassed = true;

        foreach ($checkDirs as $name => $path) {
            if (! File::exists($path)) {
                @File::makeDirectory($path, 0775, true, true);
            }
            $isWritable = is_writable($path);
            $dirResults[$name] = [
                'path' => $name,
                'passed' => $isWritable,
                'required' => true,
            ];
            if (! $isWritable) {
                $dirsPassed = false;
            }
        }

        // Check .env writable or base dir writable if .env does not exist yet
        $envPath = base_path('.env');
        $envWritable = File::exists($envPath) ? is_writable($envPath) : is_writable(base_path());
        $dirResults['.env'] = [
            'path' => '.env',
            'passed' => $envWritable,
            'required' => true,
        ];
        if (! $envWritable) {
            $dirsPassed = false;
        }

        $allPassed = $phpPassed && $extensionsPassed && $dirsPassed;

        return [
            'all_passed' => $allPassed,
            'php_version' => [
                'current' => $currentPhp,
                'required' => $minPhpVersion,
                'passed' => $phpPassed,
            ],
            'extensions' => $extensionResults,
            'directories' => $dirResults,
        ];
    }

    /**
     * Automatically attempt to fix directory permissions.
     *
     * @return array{success: bool, dir_count: int, file_count: int, message: string}
     */
    public static function autoFixPermissions(): array
    {
        $baseDir = base_path();
        $dirCount = 0;
        $fileCount = 0;

        try {
            // Fix storage and bootstrap/cache permissions
            $writableFolders = [
                storage_path(),
                storage_path('app'),
                storage_path('app/public'),
                storage_path('framework'),
                storage_path('framework/cache'),
                storage_path('framework/cache/data'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                storage_path('logs'),
                base_path('bootstrap/cache'),
            ];

            foreach ($writableFolders as $dir) {
                if (! File::exists($dir)) {
                    @File::makeDirectory($dir, 0775, true, true);
                }
                @chmod($dir, 0775);
                $dirCount++;
            }

            // Also check .env
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                @chmod($envPath, 0664);
                $fileCount++;
            }

            return [
                'success' => true,
                'dir_count' => $dirCount,
                'file_count' => $fileCount,
                'message' => 'Permission folder storage, bootstrap/cache, dan .env berhasil diperbarui ke 0775 / 0664.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'dir_count' => $dirCount,
                'file_count' => $fileCount,
                'message' => 'Gagal mengubah permission: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Test direct database connection using PDO.
     *
     * @param  array{host: string, port: string|int, database: string, username: string, password: ?string}  $config
     * @return array{success: bool, message: string}
     */
    public static function testDbConnection(array $config): array
    {
        try {
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $database = $config['database'] ?? '';
            $username = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return [
                'success' => true,
                'message' => 'Koneksi ke database "'.$database.'" berhasil!',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke database: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Update .env file with new values.
     *
     * @param  array<string, string|int|null>  $values
     */
    public static function updateEnv(array $values): bool
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (! File::exists($envPath)) {
            if (File::exists($examplePath)) {
                File::copy($examplePath, $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        $content = File::get($envPath);

        foreach ($values as $key => $value) {
            $value = (string) $value;

            // Quote value if it contains spaces or special characters
            if (preg_match('/\s/', $value) || str_contains($value, '#') || str_contains($value, '=')) {
                $value = '"'.addcslashes($value, '"').'"';
            }

            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $content);

        return true;
    }

    /**
     * Run Laravel database migrations.
     *
     * @return array{success: bool, output: string}
     */
    public static function runMigrations(): array
    {
        try {
            // Reconnect DB with latest .env config
            DB::purge('mysql');
            DB::reconnect('mysql');

            $exitCode = Artisan::call('migrate', [
                '--force' => true,
            ]);

            $output = Artisan::output();

            return [
                'success' => $exitCode === 0,
                'output' => $output,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create Super Admin account and save initial ISP settings.
     *
     * @param  array{
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string,
     *     admin_phone?: ?string,
     *     company_name?: ?string,
     *     company_phone?: ?string,
     *     company_address?: ?string,
     *     seed_sample_data?: bool
     * }  $data
     * @return array{success: bool, message: string}
     */
    public static function createAdminAndSettings(array $data): array
    {
        try {
            DB::purge('mysql');
            DB::reconnect('mysql');

            // 1. Create or update Super Admin
            $admin = User::updateOrCreate(
                ['email' => $data['admin_email']],
                [
                    'name' => $data['admin_name'],
                    'password' => Hash::make($data['admin_password']),
                    'role' => 'admin',
                    'is_active' => true,
                    'phone' => $data['admin_phone'] ?? null,
                ]
            );

            // 2. Setup Company Settings
            $companyName = $data['company_name'] ?: 'NetSpeed Internet Service';
            $companyPhone = $data['company_phone'] ?: ($data['admin_phone'] ?? '0812-3456-7890');
            $companyAddress = $data['company_address'] ?: 'Kantor Pusat ISP';

            IspSetting::set('company_name', $companyName);
            IspSetting::set('company_phone', $companyPhone);
            IspSetting::set('company_address', $companyAddress);
            IspSetting::set('default_isolir_profile', 'ISOLIR');
            IspSetting::set('use_unique_code', '1');

            // 3. Optional Sample Data Seeding
            if (! empty($data['seed_sample_data'])) {
                // Ensure default packages exist
                if (InternetPackage::count() === 0) {
                    InternetPackage::create([
                        'name' => 'Home Basic 10 Mbps',
                        'price' => 150000,
                        'speed_rate' => '10M/10M',
                        'mikrotik_profile' => '10M-HOME',
                        'type' => 'pppoe',
                        'is_active' => true,
                        'description' => 'Paket internet hemat untuk kebutuhan browsing dan media sosial keluarga.',
                    ]);
                    InternetPackage::create([
                        'name' => 'Home Pro 30 Mbps',
                        'price' => 250000,
                        'speed_rate' => '30M/30M',
                        'mikrotik_profile' => '30M-HOME',
                        'type' => 'pppoe',
                        'is_active' => true,
                        'description' => 'Paket internet cepat untuk streaming 4K dan bermain game lancar.',
                    ]);
                    InternetPackage::create([
                        'name' => 'Business Ultra 100 Mbps',
                        'price' => 500000,
                        'speed_rate' => '100M/100M',
                        'mikrotik_profile' => '100M-BIZ',
                        'type' => 'pppoe',
                        'is_active' => true,
                        'description' => 'Paket dedicated super cepat untuk kantor dan produktivitas tinggi.',
                    ]);
                }

                // Ensure default router exists
                if (Router::count() === 0) {
                    Router::create([
                        'name' => 'MikroTik Core - RB4011',
                        'host' => '192.168.88.1',
                        'port' => 8728,
                        'username' => 'admin',
                        'password' => 'admin',
                        'use_ssl' => false,
                        'timeout' => 5,
                        'status' => 'offline',
                        'board_name' => 'RB4011iGS+',
                        'ros_version' => '7.12',
                        'notes' => 'Router Gateway Utama PPPoE',
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => 'Akun Super Admin dan profil ISP berhasil disimpan.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal membuat akun/pengaturan: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Finalize installation and lock the installer.
     */
    public static function finalizeInstallation(): void
    {
        // 1. Generate APP_KEY if missing
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // 2. Create storage symlink
        try {
            Artisan::call('storage:link', ['--force' => true]);
        } catch (Throwable) {
            // Ignore if link already exists or symlink unsupported
        }

        // 3. Clear and optimize caches
        try {
            Artisan::call('optimize:clear');
        } catch (Throwable) {
            // Ignore
        }

        // 4. Create lock file
        $lockFile = storage_path('installed');
        File::put($lockFile, json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_version' => '1.0.0',
            'installed_by' => 'Web Installer Wizard',
        ], JSON_PRETTY_PRINT));
    }
}
