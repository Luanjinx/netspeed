<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->string('approval_status')->default('approved')->after('is_active')->index();
            $table->text('rejection_reason')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'approval_status',
                'rejection_reason',
                'approved_at',
            ]);
        });
    }
};
