<?php

namespace App\Models;

use App\Traits\BelongsToLab;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use BelongsToLab, HasPublicUuid, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'is_urgent', 'discount', 'net_amount', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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

    public const STEP_COLLECT = 'collect';
    public const STEP_RESULTS = 'results';
    public const STEP_VERIFY = 'verify';
    public const STEP_RELEASE = 'release';
    public const STEP_DONE = 'done';
    public const STEP_CANCELLED = 'cancelled';

    public const STEP_LABELS = [
        self::STEP_COLLECT => 'Collect sample',
        self::STEP_RESULTS => 'Enter results',
        self::STEP_VERIFY => 'Verify results',
        self::STEP_RELEASE => 'Release report',
        self::STEP_DONE => 'Report ready',
        self::STEP_CANCELLED => 'Cancelled',
    ];

    /**
     * The single next thing to do with this order. Must stay in step with
     * SampleIndex::applyQueue(), which expresses the same rules in SQL.
     */
    public function workflowStep(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return self::STEP_CANCELLED;
        }

        $this->loadMissing('items.sample', 'items.result');

        if ($this->allResultsReleased()) {
            return self::STEP_DONE;
        }

        if ($this->items->contains(fn (OrderItem $item) => ! $item->sample?->isCollected())) {
            return self::STEP_COLLECT;
        }

        if ($this->items->contains(fn (OrderItem $item) => ! $item->result)) {
            return self::STEP_RESULTS;
        }

        if ($this->items->contains(fn (OrderItem $item) => $item->result->isDraft())) {
            return self::STEP_VERIFY;
        }

        return self::STEP_RELEASE;
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