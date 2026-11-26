<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'payment_reference',
        'amount_paid',
        'payment_method',
        'proof_image',
        'is_verified',
        'verified_by',
        'verified_at',
        'raw_response',
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
