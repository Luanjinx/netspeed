<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('withdrawal_code')->unique()->index();
            $table->decimal('amount', 14, 2);
            $table->string('method')->default('bank_transfer'); // bank_transfer, ewallet, cash
            $table->string('bank_or_provider')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_withdrawals');
    }
};
