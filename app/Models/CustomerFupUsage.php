<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFupUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'period',
        'bytes_download',
        'bytes_upload',
        'total_bytes',
        'last_seen_rx',
        'last_seen_tx',
        'fup_applied_at',
    ];

    protected $casts = [
        'bytes_download' => 'integer',
        'bytes_upload' => 'integer',
        'total_bytes' => 'integer',
        'last_seen_rx' => 'integer',
        'last_seen_tx' => 'integer',
        'fup_applied_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getUsageGbAttribute(): float
    {
        return round($this->total_bytes / (1024 * 1024 * 1024), 2);
    }

    public function getUsageFormattedAttribute(): string
    {
        $bytes = $this->total_bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }
}
