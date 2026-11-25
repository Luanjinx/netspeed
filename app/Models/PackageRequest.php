<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageRequest extends Model
{
    use HasFactory;

    public const TYPE_REGISTRATION = 'registration';

    public const TYPE_UPGRADE = 'upgrade';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const PAYMENT_NOT_REQUIRED = 'not_required';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'request_number',
        'customer_id',
        'type',
        'current_package_id',
        'requested_package_id',
        'invoice_id',
        'work_order_id',
        'status',
        'payment_status',
        'customer_notes',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PackageRequest $request): void {
            if (empty($request->request_number)) {
                $prefix = 'REQ-'.date('Ym').'-';
                $lastNumber = static::where('request_number', 'like', $prefix.'%')
                    ->orderByDesc('id')
                    ->value('request_number');

                $nextSeq = 1;
                if ($lastNumber && preg_match('/(\d+)$/', $lastNumber, $matches)) {
                    $nextSeq = ((int) $matches[1]) + 1;
                }

                $request->request_number = $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currentPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'current_package_id');
    }

    public function requestedPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'requested_package_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function isReadyForApproval(): bool
    {
        return $this->isPending() && $this->payment_status !== self::PAYMENT_UNPAID;
    }
}
