<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'package_id',
        'period',
        'amount',
        'unique_code',
        'total_amount',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
        'qris_payload',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'unique_code' => 'integer',
        'total_amount' => 'decimal:2',
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'package_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function agentTransactions(): HasMany
    {
        return $this->hasMany(AgentTransaction::class);
    }
}
