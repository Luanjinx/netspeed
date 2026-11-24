<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'package_id',
        'router_id',
        'hotspot_voucher_id',
        'buyer_name',
        'buyer_phone',
        'amount',
        'total_amount',
        'payment_method',
        'status',
        'qris_payload',
        'voucher_code',
        'voucher_password',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
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

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(HotspotVoucher::class, 'hotspot_voucher_id');
    }
}
