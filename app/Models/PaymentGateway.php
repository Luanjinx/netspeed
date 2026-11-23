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

    protected $appends = [
        'fee_formatted',
        'logo_url',
    ];

    /**
     * Scope for active gateways sorted by order.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    /**
     * Get accessible URL for logo (direct URL, predefined vector asset, or stored asset).
     */
    public function getLogoUrlAttribute(): ?string
    {
        $codeMap = [
            'qris_manual' => 'qris.svg',
            'tripay' => 'tripay.svg',
            'duitku' => 'duitku.svg',
            'midtrans' => 'midtrans.svg',
            'xendit' => 'xendit.svg',
            'cekmutasi' => 'cekmutasi.svg',
            'moota' => 'moota.svg',
            'manual_transfer' => 'bank.svg',
        ];

        if (empty($this->logo)) {
            if (isset($codeMap[$this->code]) && file_exists(public_path('images/gateways/'.$codeMap[$this->code]))) {
                return asset('images/gateways/'.$codeMap[$this->code]);
            }

            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        $cleanName = basename($this->logo);
        $extensions = ['.svg', '.png', '.webp', '.jpg'];

        foreach ($extensions as $ext) {
            $candidate = 'images/gateways/'.(str_ends_with(strtolower($cleanName), $ext) ? $cleanName : $cleanName.$ext);
            if (file_exists(public_path($candidate))) {
                return asset($candidate);
            }
        }

        if (file_exists(public_path('images/gateways/'.$cleanName))) {
            return asset('images/gateways/'.$cleanName);
        }

        if (str_starts_with($this->logo, 'storage/') && file_exists(public_path($this->logo))) {
            return asset($this->logo);
        }

        if (file_exists(public_path('storage/'.$this->logo))) {
            return asset('storage/'.$this->logo);
        }

        if (isset($codeMap[$this->code]) && file_exists(public_path('images/gateways/'.$codeMap[$this->code]))) {
            return asset('images/gateways/'.$codeMap[$this->code]);
        }

        return null;
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
