<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all existing customers with non-numeric customer numbers (like CUST-*) to random 8-digit numbers
        $customers = Customer::whereRaw('customer_number NOT REGEXP "^[0-9]+$"')->get();

        foreach ($customers as $customer) {
            $customer->update([
                'customer_number' => Customer::generateCustomerNumber(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for numeric customer numbers
    }
};
