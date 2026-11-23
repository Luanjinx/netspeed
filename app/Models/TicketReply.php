<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'sender_type',
        'message',
        'attachment',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    protected $appends = [
        'sender_name',
        'sender_role_label',
        'attachment_url',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getSenderNameAttribute(): string
    {
        if (! $this->user) {
            return match ($this->sender_type) {
                'admin' => 'Customer Care NetSpeed',
                'technician' => 'Teknisi NetSpeed',
                default => 'Pelanggan',
            };
        }

        return $this->user->name;
    }

    public function getSenderRoleLabelAttribute(): string
    {
        return match ($this->sender_type) {
            'admin' => 'Customer Care',
            'technician' => 'Teknisi Lapangan',
            default => 'Pelanggan',
        };
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (empty($this->attachment)) {
            return null;
        }

        if (str_starts_with($this->attachment, 'http://') || str_starts_with($this->attachment, 'https://')) {
            return $this->attachment;
        }

        return Storage::disk('public')->url($this->attachment);
    }
}
