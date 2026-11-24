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
        Schema::create('voucher_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('package_id')->constrained('internet_packages')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->foreignId('hotspot_voucher_id')->nullable()->constrained('hotspot_vouchers')->nullOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone');
            $table->decimal('amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_method')->default('qris_manual');
            $table->string('status')->default('pending'); // pending, paid, failed, cancelled
            $table->text('qris_payload')->nullable();
            $table->string('voucher_code')->nullable();
            $table->string('voucher_password')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_orders');
    }
};
