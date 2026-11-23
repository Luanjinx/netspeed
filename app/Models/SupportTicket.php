<?php

namespace App\Models;

use App\Services\Notification\TelegramService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'technician_id',
        'subject',
        'description',
        'priority',
        'status',
        'optical_power_dbm',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'support_ticket_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'support_ticket_id')->orderBy('created_at', 'asc');
    }

    protected static function booted(): void
    {
        static::created(function (SupportTicket $ticket) {
            try {
                app(TelegramService::class)->notifyTicketCreated($ticket);
            } catch (\Throwable $e) {
                Log::warning('Telegram ticket notification error: '.$e->getMessage());
            }
        });
    }
}
