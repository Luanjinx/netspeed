<?php

namespace App\Models;

use App\Services\Router\MikrotikApiService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class InternetPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'speed_rate',
        'price',
        'promo_price',
        'promo_cycles',
        'is_prorated',
        'validity_days',
        'shared_users',
        'mikrotik_profile',
        'mikrotik_isolir_profile',
        'type',
        'fup_enabled',
        'fup_limit_gb',
        'fup_throttle_speed',
        'fup_mikrotik_profile',
        'description',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'promo_price' => 'decimal:2',
        'promo_cycles' => 'integer',
        'is_prorated' => 'boolean',
        'validity_days' => 'integer',
        'shared_users' => 'integer',
        'fup_enabled' => 'boolean',
        'fup_limit_gb' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (InternetPackage $package) {
            if (! empty($package->mikrotik_profile) && $package->is_active) {
                try {
                    app(MikrotikApiService::class)->syncInternetPackageProfile($package);
                } catch (\Throwable $e) {
                    Log::warning("Gagal auto-sync profil paket ke MikroTik: {$e->getMessage()}");
                }
            }
        });
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'package_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'package_id');
    }
}
