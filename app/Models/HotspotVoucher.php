<?php

namespace App\Models;

use App\Services\Hotspot\VoucherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class HotspotVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'code',
        'password',
        'profile',
        'shared_users',
        'price',
        'validity',
        'status',
        'generated_batch',
        'used_at',
        'used_by_mac',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'shared_users' => 'integer',
        'used_at' => 'datetime',
    ];

    public bool $skip_router_deletion = false;

    protected static function booted(): void
    {
        static::deleting(function (HotspotVoucher $voucher) {
            if ($voucher->skip_router_deletion) {
                return;
            }

            if ($voucher->router) {
                try {
                    app(VoucherService::class)->removeSingleVoucherFromRouter($voucher);
                } catch (\Throwable $e) {
                    Log::warning("Failed to remove voucher {$voucher->code} on model delete: ".$e->getMessage());
                }
            }
        });
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }
}
