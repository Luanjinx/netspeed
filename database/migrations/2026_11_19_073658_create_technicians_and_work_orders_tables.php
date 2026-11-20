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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('work_area')->nullable()->after('is_active');
            $table->string('technician_code')->nullable()->after('work_area');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->after('package_id')->constrained('users')->nullOnDelete();
            $table->string('optical_power_dbm')->nullable()->after('technician_id');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('technician_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            $table->string('optical_power_dbm')->nullable()->after('status');
            $table->text('resolution_notes')->nullable()->after('optical_power_dbm');
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('type')->default('installation'); // installation, repair, maintenance
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->date('scheduled_date')->nullable();
            $table->string('optical_power_dbm')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['technician_id']);
            $table->dropColumn(['technician_id', 'optical_power_dbm', 'resolution_notes']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['technician_id']);
            $table->dropColumn(['technician_id', 'optical_power_dbm']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'work_area', 'technician_code']);
        });
    }
};
