<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\GenieacsServer;
use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Router;
use App\Models\User;
use App\Services\Billing\BillingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@isp.net'],
            [
                'name' => 'Administrator ISP',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '081234567890',
            ]
        );

        // 2. Default ISP Settings
        // Sample Indonesian Static QRIS (Standard EMVCo format for testing/demo)
        $sampleStaticQris = '00020101021126680016ID.CO.QRIS.WWW011893600999000000000102150000000000000010303UMI51440014ID.LINKAJA.WWW02150000000000000010303UMI5204481453033605802ID5916NETSPEED BILLING6007JAKARTA61051234063047209';

        IspSetting::set('company_name', 'NetSpeed Internet Service');
        IspSetting::set('company_phone', '0812-3456-7890');
        IspSetting::set('company_address', 'Jl. Telekomunikasi No. 88, Jakarta');
        IspSetting::set('static_qris_string', $sampleStaticQris);
        IspSetting::set('default_isolir_profile', 'ISOLIR');
        IspSetting::set('use_unique_code', '1');
        IspSetting::set('whatsapp_gateway_url', 'https://api.fonnte.com/send');
        IspSetting::set('whatsapp_api_key', '');

        // 3. Create Default Router
        $router = Router::firstOrCreate(
            ['name' => 'MikroTik Core - RB4011'],
            [
                'host' => '192.168.88.1',
                'port' => 8728,
                'username' => 'admin',
                'password' => 'admin',
                'use_ssl' => false,
                'timeout' => 5,
                'status' => 'offline',
                'board_name' => 'RB4011iGS+',
                'ros_version' => '7.12',
                'notes' => 'Router Utama Gateway Pelanggan PPPoE',
            ]
        );

        // 4. Create Default GenieACS Server
        $genieacs = GenieacsServer::firstOrCreate(
            ['name' => 'GenieACS TR-069 Server'],
            [
                'base_url' => 'http://127.0.0.1:7557',
                'username' => '',
                'password' => '',
                'auth_token' => '',
                'status' => 'offline',
                'notes' => 'Server TR-069 Manajemen ONT Pelanggan',
            ]
        );

        // 5. Internet Packages
        $pkg10 = InternetPackage::firstOrCreate(
            ['name' => 'Home Basic 10 Mbps'],
            [
                'speed_rate' => '10M/10M',
                'price' => 100000,
                'validity_days' => 30,
                'mikrotik_profile' => 'PROFILE-10M',
                'mikrotik_isolir_profile' => 'ISOLIR',
                'type' => 'pppoe',
                'description' => 'Paket ekonomis browsing & streaming 1-3 perangkat',
            ]
        );

        $pkg20 = InternetPackage::firstOrCreate(
            ['name' => 'Home Ultra 20 Mbps'],
            [
                'speed_rate' => '20M/20M',
                'price' => 150000,
                'validity_days' => 30,
                'mikrotik_profile' => 'PROFILE-20M',
                'mikrotik_isolir_profile' => 'ISOLIR',
                'type' => 'pppoe',
                'description' => 'Paket keluarga cepat, streaming 4K & game online',
            ]
        );

        $pkg50 = InternetPackage::firstOrCreate(
            ['name' => 'Gamer Pro 50 Mbps'],
            [
                'speed_rate' => '50M/50M',
                'price' => 250000,
                'validity_days' => 30,
                'mikrotik_profile' => 'PROFILE-50M',
                'mikrotik_isolir_profile' => 'ISOLIR',
                'type' => 'pppoe',
                'description' => 'Paket premium ultra cepat dengan prioritas bandwidth',
            ]
        );

        // 6. Create Sample Customer & User for Flutter App Testing
        $customerUser = User::firstOrCreate(
            ['email' => 'budi@gmail.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('pelanggan123'),
                'role' => 'customer',
                'phone' => '081298765432',
            ]
        );

        $customer = Customer::firstOrCreate(
            ['phone' => '081298765432'],
            [
                'customer_number' => Customer::generateCustomerNumber(),
                'user_id' => $customerUser->id,
                'name' => 'Budi Santoso',
                'phone' => '081298765432',
                'email' => 'budi@gmail.com',
                'address' => 'Jl. Mawar No. 12, RT 03 / RW 05',
                'router_id' => $router->id,
                'package_id' => $pkg20->id,
                'connection_type' => 'pppoe',
                'pppoe_username' => 'budi_santoso',
                'pppoe_password' => 'secret123',
                'genieacs_server_id' => $genieacs->id,
                'genieacs_device_id' => '202BC1-HG680-ZTEG12345678',
                'ont_serial_number' => 'ZTEG12345678',
                'status' => 'active',
                'registered_at' => now()->subMonths(2),
                'due_date_day' => 10,
                'auto_isolate' => true,
            ]
        );

        // 7. Generate Initial Invoice for Customer
        app(BillingService::class)->generateInvoice($customer, now()->format('Y-m'));

        // 8. Seed Default Multi Payment Gateways
        $this->call(PaymentGatewaySeeder::class);
    }
}
