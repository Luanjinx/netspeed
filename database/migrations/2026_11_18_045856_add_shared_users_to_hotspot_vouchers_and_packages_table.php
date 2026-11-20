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
        Schema::table('hotspot_vouchers', function (Blueprint $table) {
            $table->unsignedSmallInteger('shared_users')->default(1)->after('profile');
        });

        Schema::table('internet_packages', function (Blueprint $table) {
            $table->unsignedSmallInteger('shared_users')->default(1)->after('validity_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotspot_vouchers', function (Blueprint $table) {
            $table->dropColumn('shared_users');
        });

        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropColumn('shared_users');
        });
    }
};
