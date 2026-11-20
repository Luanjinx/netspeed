<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Technician extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('technician', function (Builder $builder) {
            $builder->where('role', 'technician');
        });

        static::creating(function (Technician $tech) {
            $tech->role = 'technician';
            if (empty($tech->technician_code)) {
                $count = static::withoutGlobalScope('technician')->where('role', 'technician')->count() + 1;
                $tech->technician_code = 'TECH-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
