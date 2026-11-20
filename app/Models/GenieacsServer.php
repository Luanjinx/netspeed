<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GenieacsServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'username',
        'password',
        'auth_token',
        'status',
        'last_checked_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
