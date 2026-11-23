<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'work_area',
        'technician_code',
        'phone',
        'avatar',
        'fcm_token',
        'onesignal_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine if user can access Filament Admin Panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Scope query to technicians.
     */
    public function scopeTechnicians(Builder $query): Builder
    {
        return $query->where('role', 'technician');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'technician_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'technician_id');
    }

    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'technician_id');
    }

    /**
     * Customer profile relation if this user is a customer.
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Agent profile relation if this user is an agent.
     */
    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }
}
