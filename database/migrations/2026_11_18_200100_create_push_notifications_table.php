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
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('target_type')->default('all'); // all, customer, active_customers, isolated_customers, external_user_ids
            $table->string('target_label')->nullable();
            $table->json('external_ids')->nullable();
            $table->string('image_url')->nullable();
            $table->string('action_url')->nullable();
            $table->json('data_payload')->nullable();
            $table->string('status')->default('sent'); // sent, failed, pending
            $table->integer('recipients_count')->default(0);
            $table->string('onesignal_id')->nullable();
            $table->text('response_summary')->nullable();
            $table->timestamps();
        });

        // Add onesignal_id to customers & users for targeting
        if (! Schema::hasColumn('customers', 'onesignal_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('onesignal_id')->nullable()->after('genieacs_device_id');
            });
        }

        if (! Schema::hasColumn('users', 'onesignal_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('onesignal_id')->nullable()->after('fcm_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notifications');

        if (Schema::hasColumn('customers', 'onesignal_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('onesignal_id');
            });
        }

        if (Schema::hasColumn('users', 'onesignal_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('onesignal_id');
            });
        }
    }
};
