<?php

namespace App\Models;

use App\Services\Router\MikrotikApiService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_number',
        'user_id',
        'name',
        'phone',
        'email',
        'password',
        'address',
        'latitude',
        'longitude',
        'cable_path',
        'router_id',
        'odp_id',
        'odp_port',
        'package_id',
        'technician_id',
        'optical_power_dbm',
        'connection_type',
        'pppoe_username',
        'pppoe_password',
        'ip_address',
        'mac_address',
        'genieacs_server_id',
        'genieacs_device_id',
        'onesignal_id',
        'ont_serial_number',
        'status',
        'fup_status',
        'fup_override_limit_gb',
        'fup_notified_at',
        'registered_at',
        'install_date',
        'promo_cycles_used',
        'due_date_day',
        'auto_isolate',
        'notes',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'registered_at' => 'date',
        'install_date' => 'date',
        'promo_cycles_used' => 'integer',
        'due_date_day' => 'integer',
        'fup_override_limit_gb' => 'integer',
        'fup_notified_at' => 'datetime',
        'odp_port' => 'integer',
        'auto_isolate' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'cable_path' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->customer_number)) {
                $customer->customer_number = self::generateCustomerNumber();
            }
        });

        static::saving(function (Customer $customer) {
            if ($customer->exists && $customer->isDirty('package_id')) {
                $customer->promo_cycles_used = 0;
            }

            if ($customer->isDirty('password') && filled($customer->password)) {
                if ($customer->user) {
                    $customer->user->update([
                        'name' => $customer->name,
                        'email' => $customer->email ?: $customer->user->email,
                        'phone' => $customer->phone,
                        'password' => $customer->password,
                    ]);
                } elseif (filled($customer->phone) || filled($customer->email)) {
                    $user = User::updateOrCreate(
                        ['phone' => $customer->phone],
                        [
                            'name' => $customer->name,
                            'email' => $customer->email ?: "{$customer->customer_number}@customer.netspeed",
                            'phone' => $customer->phone,
                            'password' => $customer->password,
                            'role' => 'customer',
                        ]
                    );
                    $customer->user_id = $user->id;
                }
            }
        });

        static::deleting(function (Customer $customer) {
            if ($customer->connection_type === 'pppoe' && $customer->router && ! empty($customer->pppoe_username)) {
                try {
                    app(MikrotikApiService::class)->removePppSecret($customer->router, $customer->pppoe_username);
                } catch (\Throwable $e) {
                    Log::warning("Gagal hapus PPPoE secret saat customer dihapus: {$e->getMessage()}");
                }
            }
        });
    }

    /**
     * Generate unique numeric-only customer number (8 digits, e.g. 10000000 - 99999999).
     */
    public static function generateCustomerNumber(): string
    {
        do {
            $number = (string) random_int(10000000, 99999999);
        } while (self::where('customer_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'package_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function genieacsServer(): BelongsTo
    {
        return $this->belongsTo(GenieacsServer::class, 'genieacs_server_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function fupUsages(): HasMany
    {
        return $this->hasMany(CustomerFupUsage::class);
    }

    public function currentMonthFupUsage()
    {
        return $this->hasOne(CustomerFupUsage::class)->where('period', date('Y-m'));
    }
}
