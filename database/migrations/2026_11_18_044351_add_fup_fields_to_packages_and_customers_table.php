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
        Schema::table('internet_packages', function (Blueprint $table) {
            $table->boolean('fup_enabled')->default(false)->after('type');
            $table->unsignedInteger('fup_limit_gb')->nullable()->after('fup_enabled');
            $table->string('fup_throttle_speed')->nullable()->after('fup_limit_gb');
            $table->string('fup_mikrotik_profile')->nullable()->after('fup_throttle_speed');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('fup_status')->default('normal')->after('status');
            $table->unsignedInteger('fup_override_limit_gb')->nullable()->after('fup_status');
            $table->timestamp('fup_notified_at')->nullable()->after('fup_override_limit_gb');
        });

        Schema::create('customer_fup_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('period', 7)->index(); // YYYY-MM
            $table->unsignedBigInteger('bytes_download')->default(0);
            $table->unsignedBigInteger('bytes_upload')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->unsignedBigInteger('last_seen_rx')->default(0);
            $table->unsignedBigInteger('last_seen_tx')->default(0);
            $table->timestamp('fup_applied_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_fup_usages');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['fup_status', 'fup_override_limit_gb', 'fup_notified_at']);
        });

        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropColumn(['fup_enabled', 'fup_limit_gb', 'fup_throttle_speed', 'fup_mikrotik_profile']);
        });
    }
};
