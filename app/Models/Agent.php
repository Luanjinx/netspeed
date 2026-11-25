<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'agent_code',
        'name',
        'phone',
        'email',
        'address',
        'balance',
        'commission_per_transaction',
        'is_active',
        'approval_status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'commission_per_transaction' => 'decimal:2',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AgentTransaction::class)->latest();
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::STATUS_REJECTED;
    }

    public function canOperate(): bool
    {
        return (bool) $this->is_active && $this->isApproved();
    }

    public static function generateNextCode(): string
    {
        $nextNumber = ((int) self::max('id')) + 1;
        $code = 'AGT-'.sprintf('%03d', $nextNumber);

        while (self::where('agent_code', $code)->exists()) {
            $nextNumber++;
            $code = 'AGT-'.sprintf('%03d', $nextNumber);
        }

        return $code;
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return (float) $this->balance >= $amount;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp '.number_format((float) $this->balance, 0, ',', '.');
    }
}
