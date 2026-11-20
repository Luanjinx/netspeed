<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'type',
        'technician_id',
        'customer_id',
        'support_ticket_id',
        'title',
        'description',
        'status',
        'scheduled_date',
        'optical_power_dbm',
        'completion_notes',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'SPK-'.date('Ym').'-'.str_pad((string) (static::whereYear('created_at', date('Y'))->whereMonth('created_at', date('m'))->count() + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
