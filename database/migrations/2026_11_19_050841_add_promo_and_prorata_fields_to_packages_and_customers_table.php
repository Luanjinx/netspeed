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
            $table->decimal('promo_price', 12, 2)->nullable()->after('price');
            $table->unsignedInteger('promo_cycles')->default(0)->after('promo_price');
            $table->boolean('is_prorated')->default(false)->after('promo_cycles');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->date('install_date')->nullable()->after('registered_at');
            $table->unsignedInteger('promo_cycles_used')->default(0)->after('install_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropColumn(['promo_price', 'promo_cycles', 'is_prorated']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['install_date', 'promo_cycles_used']);
        });
    }
};
