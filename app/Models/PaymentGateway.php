<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'logo',
        'description',
        'fee_type',
        'fee_flat',
        'fee_percent',
        'instructions',
        'config',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'fee_flat' => 'decimal:2',
        'fee_percent' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active gateways sorted by order.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    /**
     * Formatted Fee display string.
     */
    public function getFeeFormattedAttribute(): string
    {
        if ($this->fee_type === 'percent') {
            return ((float) $this->fee_percent > 0) ? (float) $this->fee_percent.'%' : '0% (Gratis)';
        }

        return ((float) $this->fee_flat > 0)
            ? 'Rp '.number_format((float) $this->fee_flat, 0, ',', '.')
            : '0% (Gratis)';
    }

    /**
     * Calculate total fee for a given amount.
     */
    public function calculateFee(float $amount): float
    {
        if ($this->fee_type === 'percent') {
            return ($amount * (float) $this->fee_percent) / 100;
        }

        return (float) $this->fee_flat;
    }
}
