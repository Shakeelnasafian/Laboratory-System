<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    public const FLAG_NORMAL = 'normal';
    public const FLAG_HIGH = 'high';
    public const FLAG_LOW = 'low';
    public const FLAG_CRITICAL = 'critical';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_RELEASED = 'released';

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
}