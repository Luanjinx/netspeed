<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update users table with role and phone
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email'); // admin, technician, customer
            $table->string('phone')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('phone');
        });

        // 2. Routers (MikroTik local & external/cloud/VPN)
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->integer('port')->default(8728);
            $table->string('username');
            $table->text('password');
            $table->boolean('use_ssl')->default(false);
            $table->integer('timeout')->default(5);
            $table->string('status')->default('offline'); // online, offline, error
            $table->timestamp('last_connected_at')->nullable();
            $table->string('board_name')->nullable();
            $table->string('model_name')->nullable();
            $table->string('ros_version')->nullable();
            $table->integer('cpu_load')->nullable();
            $table->string('uptime')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. GenieACS Servers (TR-069 ACS local & external)
        Schema::create('genieacs_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url'); // e.g. http://103.x.x.x:7557
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('auth_token')->nullable();
            $table->string('status')->default('offline'); // online, offline, error
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Internet Packages
        Schema::create('internet_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('speed_rate'); // e.g. 20M/20M
            $table->decimal('price', 12, 2);
            $table->integer('validity_days')->default(30);
            $table->string('mikrotik_profile'); // PPPoE / Hotspot profile name in MikroTik
            $table->string('mikrotik_isolir_profile')->nullable();
            $table->string('type')->default('pppoe'); // pppoe, hotspot, static
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('internet_packages')->nullOnDelete();
            $table->string('connection_type')->default('pppoe'); // pppoe, hotspot, static
            $table->string('pppoe_username')->nullable();
            $table->string('pppoe_password')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->foreignId('genieacs_server_id')->nullable()->constrained('genieacs_servers')->nullOnDelete();
            $table->string('genieacs_device_id')->nullable(); // GenieACS Device ID
            $table->string('ont_serial_number')->nullable();
            $table->string('status')->default('active'); // active, isolated, suspended, pending
            $table->date('registered_at')->nullable();
            $table->integer('due_date_day')->default(10); // Tanggal jatuh tempo bulanan
            $table->boolean('auto_isolate')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('internet_packages')->nullOnDelete();
            $table->string('period'); // e.g. 2026-10
            $table->decimal('amount', 12, 2);
            $table->integer('unique_code')->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->default('unpaid'); // unpaid, paid, cancelled, expired
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // qris_dynamic, manual_transfer, cash
            $table->text('qris_payload')->nullable(); // Dynamic QRIS string
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('payment_reference')->unique();
            $table->decimal('amount_paid', 12, 2);
            $table->string('payment_method')->default('qris_dynamic');
            $table->string('proof_image')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('raw_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Support Tickets (Aplikasi Flutter)
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 9. ISP Settings (General, Static QRIS, WhatsApp)
        Schema::create('isp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isp_settings');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('internet_packages');
        Schema::dropIfExists('genieacs_servers');
        Schema::dropIfExists('routers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'avatar']);
        });
    }
};
