<?php

namespace App\Models;

use App\Traits\BelongsToLab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use BelongsToLab;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SAMPLE_COLLECTED = 'sample_collected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_SAMPLE_COLLECTED => 'Sample Collected',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'lab_id', 'patient_id', 'created_by', 'order_number',
        'status', 'is_urgent', 'total_amount', 'discount',
        'net_amount', 'referred_by', 'notes', 'collected_at', 'completed_at',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'collected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function allResultsVerified(): bool
    {
        $this->loadMissing('items.result');

        return $this->items->isNotEmpty() && $this->items->every(
            fn (OrderItem $item) => $item->result && in_array($item->result->status, [Result::STATUS_VERIFIED, Result::STATUS_RELEASED], true)
        );
    }

    public function allResultsReleased(): bool
    {
        $this->loadMissing('items.result');

        return $this->items->isNotEmpty() && $this->items->every(
            fn (OrderItem $item) => $item->result?->status === Result::STATUS_RELEASED
        );
    }

    public function canReleaseReport(): bool
    {
        return $this->allResultsVerified();
    }

    public function canPrintReport(): bool
    {
        return $this->allResultsReleased();
    }

    public function criticalResultsCount(): int
    {
        $this->loadMissing('items.result');

        return $this->items->filter(fn (OrderItem $item) => $item->result?->flag === Result::FLAG_CRITICAL)->count();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $date = now()->format('Ymd');
                $count = static::withoutGlobalScope('lab')
                    ->where('lab_id', $order->lab_id)
                    ->whereDate('created_at', today())
                    ->count() + 1;
                $order->order_number = 'ORD-' . $date . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}