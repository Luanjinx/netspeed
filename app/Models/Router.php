<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'use_ssl',
        'timeout',
        'status',
        'last_connected_at',
        'board_name',
        'model_name',
        'ros_version',
        'cpu_load',
        'uptime',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'use_ssl' => 'boolean',
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
        'port' => 'integer',
        'timeout' => 'integer',
        'cpu_load' => 'integer',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
