<?php

namespace App\Models;

use App\Services\Router\MikrotikApiService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        'features',
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
        'features' => 'array',
    ];

    protected $appends = [
        'image_url',
    ];

    protected static function booted(): void
    {
        static::updating(function (InternetPackage $package) {
            $oldProfile = $package->getOriginal('mikrotik_profile');
            if ($oldProfile && $oldProfile !== $package->mikrotik_profile && in_array($package->type, ['pppoe', 'hotspot'])) {
                try {
                    $dummy = clone $package;
                    $dummy->mikrotik_profile = $oldProfile;
                    app(MikrotikApiService::class)->deleteInternetPackageProfile($dummy);
                } catch (\Throwable $e) {
                    Log::warning("Gagal auto-delete profil lama MikroTik [{$oldProfile}]: {$e->getMessage()}");
                }
            }
        });

        static::saved(function (InternetPackage $package) {
            if (! empty($package->mikrotik_profile) && $package->is_active && in_array($package->type, ['pppoe', 'hotspot'])) {
                try {
                    app(MikrotikApiService::class)->syncInternetPackageProfile($package);
                } catch (\Throwable $e) {
                    Log::warning("Gagal auto-sync profil paket ke MikroTik: {$e->getMessage()}");
                }
            }
        });

        static::deleted(function (InternetPackage $package) {
            if (! empty($package->mikrotik_profile) && in_array($package->type, ['pppoe', 'hotspot'])) {
                try {
                    app(MikrotikApiService::class)->deleteInternetPackageProfile($package);
                } catch (\Throwable $e) {
                    Log::warning("Gagal auto-delete profil paket dari MikroTik: {$e->getMessage()}");
                }
            }
        });
    }

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return '';
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return url(Storage::disk('public')->url($this->image));
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
