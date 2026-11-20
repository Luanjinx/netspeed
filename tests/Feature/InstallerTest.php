<?php

namespace Tests\Feature;

use App\Services\InstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected string $lockFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockFile = storage_path('installed');
        if (File::exists($this->lockFile)) {
            File::delete($this->lockFile);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->lockFile)) {
            File::delete($this->lockFile);
        }
        parent::tearDown();
    }

    public function test_installer_step1_is_accessible_when_not_installed(): void
    {
        $response = $this->get('/install/step1');

        $response->assertStatus(200);
        $response->assertSee('Persyaratan Server & Izin Folder');
        $response->assertSee('Versi PHP');
    }

    public function test_install_root_redirects_to_step1(): void
    {
        $response = $this->get('/install');

        $response->assertRedirect(route('install.step1'));
    }

    public function test_installer_check_requirements_returns_valid_structure(): void
    {
        $requirements = InstallationService::checkRequirements();

        $this->assertArrayHasKey('all_passed', $requirements);
        $this->assertArrayHasKey('php_version', $requirements);
        $this->assertArrayHasKey('extensions', $requirements);
        $this->assertArrayHasKey('directories', $requirements);
        $this->assertTrue($requirements['php_version']['passed']);
    }

    public function test_installer_auto_fix_permissions_action(): void
    {
        $response = $this->postJson(route('install.fix_permissions'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'dir_count', 'file_count', 'message']);
        $response->assertJson(['success' => true]);
    }

    public function test_installer_step2_is_accessible(): void
    {
        $response = $this->get(route('install.step2'));

        $response->assertStatus(200);
        $response->assertSee('Konfigurasi Database & Environment');
        $response->assertSee('Database Host');
    }

    public function test_installer_test_db_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson(route('install.test_db'), [
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'non_existent_db_123456',
            'db_username' => 'invalid_user',
            'db_password' => 'invalid_password',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);
    }

    public function test_installer_step3_is_accessible(): void
    {
        $response = $this->get(route('install.step3'));

        $response->assertStatus(200);
        $response->assertSee('Migrasi Database & Struktur Tabel');
    }

    public function test_installer_step4_can_save_admin_and_settings(): void
    {
        $response = $this->from(route('install.step4'))->post(route('install.step4.save'), [
            'admin_name' => 'Owner NetSpeed',
            'admin_email' => 'owner@netspeed.id',
            'admin_password' => 'secret12345',
            'admin_password_confirmation' => 'secret12345',
            'admin_phone' => '081299887766',
            'company_name' => 'NetSpeed Network',
            'company_phone' => '081299887766',
            'company_address' => 'Gedung Cyber Lt 5',
            'seed_sample_data' => 1,
        ]);

        $response->assertRedirect(route('install.step5'));

        $this->assertDatabaseHas('users', [
            'email' => 'owner@netspeed.id',
            'name' => 'Owner NetSpeed',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('isp_settings', [
            'key' => 'company_name',
            'value' => 'NetSpeed Network',
        ]);
    }

    public function test_installer_step5_and_finish_creates_lock_file(): void
    {
        $response = $this->get(route('install.step5'));
        $response->assertStatus(200);
        $response->assertSee('Selamat! Instalasi Sukses');

        $finishResponse = $this->post(route('install.finish'));
        $finishResponse->assertRedirect('/admin');

        $this->assertFileExists($this->lockFile);

        // When locked, visiting /install redirects to /admin
        $redirectResponse = $this->get(route('install.index'));
        $redirectResponse->assertRedirect('/admin');
    }
}
