<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'total_ports',
        'splitter_ratio',
        'address',
        'latitude',
        'longitude',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_ports' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get occupied ports count.
     */
    public function getOccupiedPortsCountAttribute(): int
    {
        return $this->customers()->count();
    }

    /**
     * Get available ports count.
     */
    public function getAvailablePortsCountAttribute(): int
    {
        return max(0, $this->total_ports - $this->occupied_ports_count);
    }
}
