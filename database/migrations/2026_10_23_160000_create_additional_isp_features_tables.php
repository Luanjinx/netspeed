<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for additional ISP backend features.
     */
    public function up(): void
    {
        // 1. ODP (Optical Distribution Point)
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. ODP-MAWAR-01
            $table->string('code')->unique(); // e.g. ODP/MWR/01
            $table->integer('total_ports')->default(8); // 8, 16, 24
            $table->string('splitter_ratio')->default('1:8'); // 1:8, 1:16
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('status')->default('active'); // active, full, maintenance
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Add ODP relation to Customers
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('odp_id')->nullable()->after('router_id')->constrained('odps')->nullOnDelete();
            $table->integer('odp_port')->nullable()->after('odp_id'); // Port 1..16
        });

        // 3. Hotspot Vouchers
        Schema::create('hotspot_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('code')->unique(); // Voucher code / Username
            $table->string('password');
            $table->string('profile'); // MikroTik Hotspot Profile
            $table->decimal('price', 12, 2)->default(0);
            $table->string('validity')->default('1 Hari'); // 1 Jam, 1 Hari, 7 Hari, 30 Hari
            $table->string('status')->default('unused'); // unused, used, expired
            $table->string('generated_batch')->nullable(); // Batch identifier e.g. BATCH-20261023-01
            $table->timestamp('used_at')->nullable();
            $table->string('used_by_mac')->nullable();
            $table->timestamps();
        });

        // 4. Add FCM token to users for Mobile Push Notification
        Schema::table('users', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });

        Schema::dropIfExists('hotspot_vouchers');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['odp_id']);
            $table->dropColumn(['odp_id', 'odp_port']);
        });

        Schema::dropIfExists('odps');
    }
};
