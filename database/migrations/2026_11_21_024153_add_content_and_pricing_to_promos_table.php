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
        Schema::table('promos', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('subtitle');
            $table->string('offer_title')->nullable()->after('content');
            $table->string('offer_duration')->nullable()->after('offer_title');
            $table->decimal('original_price', 12, 2)->nullable()->after('offer_duration');
            $table->decimal('promo_price', 12, 2)->nullable()->after('original_price');
            $table->string('discount_label')->nullable()->after('promo_price');
            $table->string('price_unit')->nullable()->default('/ bulan')->after('discount_label');
            $table->string('cta_text')->nullable()->default('Langganan Sekarang')->after('price_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn([
                'content',
                'offer_title',
                'offer_duration',
                'original_price',
                'promo_price',
                'discount_label',
                'price_unit',
                'cta_text',
            ]);
        });
    }
};
