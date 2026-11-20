<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class AdminUser extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('admin', function (Builder $builder) {
            $builder->where('role', 'admin');
        });

        static::creating(function (AdminUser $admin) {
            $admin->role = 'admin';
        });
    }
}
