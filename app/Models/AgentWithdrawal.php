<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentWithdrawal extends Model
{
    use HasFactory;

    public const MIN_AMOUNT = 10000;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_EWALLET = 'ewallet';

    public const METHOD_CASH = 'cash';

    protected $fillable = [
        'agent_id',
        'withdrawal_code',
        'amount',
        'method',
        'bank_or_provider',
        'account_number',
        'account_name',
        'notes',
        'status',
        'rejection_reason',
        'transfer_reference',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            self::METHOD_BANK => 'Transfer Bank',
            self::METHOD_EWALLET => 'E-Wallet',
            self::METHOD_CASH => 'Tunai di Kantor',
            default => strtoupper($this->method),
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format((float) $this->amount, 0, ',', '.');
    }

    public static function generateNextCode(): string
    {
        $prefix = 'WD-'.date('Ymd').'-';
        $nextNumber = ((int) self::whereDate('created_at', today())->count()) + 1;
        $code = $prefix.sprintf('%03d', $nextNumber);

        while (self::where('withdrawal_code', $code)->exists()) {
            $nextNumber++;
            $code = $prefix.sprintf('%03d', $nextNumber);
        }

        return $code;
    }
}
