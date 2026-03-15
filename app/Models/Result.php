<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Result extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['value', 'unit', 'flag', 'status', 'remarks', 'is_abnormal'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    public const FLAG_NORMAL = 'normal';
    public const FLAG_HIGH = 'high';
    public const FLAG_LOW = 'low';
    public const FLAG_CRITICAL = 'critical';

    public const FLAGS = [
        self::FLAG_NORMAL => 'Normal',
        self::FLAG_HIGH => 'High',
        self::FLAG_LOW => 'Low',
        self::FLAG_CRITICAL => 'Critical',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_RELEASED = 'released';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_VERIFIED => 'Verified',
        self::STATUS_RELEASED => 'Released',
    ];

    protected $fillable = [
        'order_item_id', 'entered_by', 'value', 'unit',
        'normal_range', 'is_abnormal', 'flag', 'remarks',
        'status', 'is_verified', 'verified_by', 'verified_at',
        'released_by', 'released_at', 'critical_alerted_at',
    ];

    protected $casts = [
        'is_abnormal' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'released_at' => 'datetime',
        'critical_alerted_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ResultRevision::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    public function isCritical(): bool
    {
        return $this->flag === self::FLAG_CRITICAL;
    }

    public function isAbnormal(): bool
    {
        return $this->is_abnormal;
    }
}
