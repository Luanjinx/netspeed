<?php

namespace App\Http\Controllers;

use App\Services\InstallationService;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    /**
     * Entrypoint: Redirect to Step 1.
     */
    public function index(): RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        return redirect()->route('install.step1');
    }

    /**
     * Step 1: System requirements and directory permissions check.
     */
    public function step1(): View|RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        $requirements = InstallationService::checkRequirements();

        return view('install.step1', [
            'requirements' => $requirements,
            'currentStep' => 1,
        ]);
    }

    /**
     * Action to automatically fix directory permissions.
     */
    public function fixPermissions(Request $request): JsonResponse|RedirectResponse
    {
        $result = InstallationService::autoFixPermissions();

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('install.step1')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Step 2: License activation form.
     */
    public function license(): View|RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        $requirements = InstallationService::checkRequirements();
        if (! $requirements['all_passed']) {
            return redirect()->route('install.step1')
                ->with('error', 'Harap lengkapi semua persyaratan server dan permission terlebih dahulu.');
        }

        return view('install.license', [
            'currentStep' => 2,
            'domain' => request()->getHost(),
            'licenseData' => LicenseService::getLicenseData(),
        ]);
    }

    /**
     * Activate license via demo license API and persist certificate.
     */
    public function activateLicense(Request $request): RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        $validated = $request->validate([
            'purchase_code' => 'required|string|max:255',
        ]);

        try {
            LicenseService::activate($validated['purchase_code'], $request->getHost());
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('install.step2')
            ->with('success', 'Lisensi berhasil diaktivasi!');
    }

    /**
     * Step 3: Environment and Database configuration form.
     */
    public function step2(): View|RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        $requirements = InstallationService::checkRequirements();
        if (! $requirements['all_passed']) {
            return redirect()->route('install.step1')
                ->with('error', 'Harap lengkapi semua persyaratan server dan permission terlebih dahulu.');
        }

        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        $forwardedProto = request()->header('x-forwarded-proto');
        $scheme = ($forwardedProto === 'https' || request()->isSecure()) ? 'https' : request()->getScheme();
        $appUrl = $scheme.'://'.request()->getHttpHost();

        return view('install.step2', [
            'appUrl' => $appUrl,
            'currentStep' => 3,
        ]);
    }

    /**
     * AJAX endpoint to test database connection before saving.
     */
    public function testDb(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $result = InstallationService::testDbConnection([
            'host' => $validated['db_host'],
            'port' => $validated['db_port'],
            'database' => $validated['db_database'],
            'username' => $validated['db_username'],
            'password' => $validated['db_password'] ?? '',
        ]);

        return response()->json($result);
    }

    /**
     * Save Step 3 configuration to .env.
     */
    public function saveStep2(Request $request): RedirectResponse
    {
        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        $validated = $request->validate([
            'app_name' => 'required|string|max:100',
            'app_url' => 'required|url',
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        // Test DB connection before saving
        $dbTest = InstallationService::testDbConnection([
            'host' => $validated['db_host'],
            'port' => $validated['db_port'],
            'database' => $validated['db_database'],
            'username' => $validated['db_username'],
            'password' => $validated['db_password'] ?? '',
        ]);

        if (! $dbTest['success']) {
            return back()->withInput()->with('error', $dbTest['message']);
        }

        InstallationService::updateEnv([
            'APP_NAME' => $validated['app_name'],
            'APP_URL' => $validated['app_url'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $validated['db_host'],
            'DB_PORT' => $validated['db_port'],
            'DB_DATABASE' => $validated['db_database'],
            'DB_USERNAME' => $validated['db_username'],
            'DB_PASSWORD' => $validated['db_password'] ?? '',
        ]);

        return redirect()->route('install.step3')
            ->with('success', 'Konfigurasi database & environment berhasil disimpan!');
    }

    /**
     * Step 4: Run Database Migrations.
     */
    public function step3(): View|RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        return view('install.step3', [
            'currentStep' => 4,
        ]);
    }

    /**
     * AJAX endpoint to execute database migrations.
     */
    public function runMigrate(): JsonResponse
    {
        if (! LicenseService::isActivated()) {
            return response()->json([
                'success' => false,
                'message' => 'Harap aktivasi lisensi terlebih dahulu.',
            ], 403);
        }

        $result = InstallationService::runMigrations();

        return response()->json($result);
    }

    /**
     * Fallback standard POST endpoint to execute database migrations without AJAX fetch.
     */
    public function runMigrateDirect(): RedirectResponse
    {
        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        $result = InstallationService::runMigrations();

        if ($result['success']) {
            return redirect()->route('install.step4')
                ->with('success', 'Migrasi database berhasil diselesaikan!');
        }

        return redirect()->route('install.step3')
            ->with('error', 'Terjadi kesalahan migrasi: '.($result['output'] ?? 'Error tidak diketahui.'));
    }

    /**
     * Step 5: Super Admin account and ISP profile setup.
     */
    public function step4(): View|RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        return view('install.step4', [
            'currentStep' => 5,
        ]);
    }

    /**
     * Save Step 5 (Create Super Admin & ISP Settings).
     */
    public function saveStep4(Request $request): RedirectResponse
    {
        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        $validated = $request->validate([
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:150',
            'admin_password' => 'required|string|min:8|confirmed',
            'admin_phone' => 'nullable|string|max:30',
            'company_name' => 'required|string|max:150',
            'company_phone' => 'nullable|string|max:30',
            'company_address' => 'nullable|string|max:255',
            'seed_sample_data' => 'nullable|boolean',
        ]);

        $result = InstallationService::createAdminAndSettings([
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
            'admin_phone' => $validated['admin_phone'] ?? null,
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'seed_sample_data' => $request->boolean('seed_sample_data'),
        ]);

        if (! $result['success']) {
            return back()->withInput()->with('error', $result['message']);
        }

        return redirect()->route('install.step5')
            ->with('success', 'Akun Super Admin & Profil ISP berhasil dikonfigurasi!');
    }

    /**
     * Step 6: Finish & Security Lock.
     */
    public function step5(): View|RedirectResponse
    {
        if (InstallationService::isInstalled()) {
            return redirect('/admin');
        }

        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        return view('install.step5', [
            'currentStep' => 6,
            'licenseData' => LicenseService::getLicenseData(),
        ]);
    }

    /**
     * Finalize installation and redirect to Filament Admin.
     */
    public function finish(): RedirectResponse
    {
        if (! LicenseService::isActivated()) {
            return redirect()->route('install.license')
                ->with('error', 'Harap aktivasi lisensi terlebih dahulu.');
        }

        InstallationService::finalizeInstallation();

        return redirect('/admin')
            ->with('success', 'Instalasi selesai! Silakan login menggunakan akun Admin yang baru saja Anda buat.');
    }
}
