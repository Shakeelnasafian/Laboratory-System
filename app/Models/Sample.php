<?php

namespace App\Models;

use App\Traits\BelongsToLab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sample extends Model
{
    use BelongsToLab;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COLLECTED = 'collected';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'lab_id',
        'order_item_id',
        'accession_number',
        'sample_type',
        'container',
        'status',
        'collected_by',
        'collected_at',
        'received_by',
        'received_at',
        'rejection_reason',
        'is_recollection',
        'label_printed_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'received_at' => 'datetime',
        'label_printed_at' => 'datetime',
        'is_recollection' => 'boolean',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isCollected(): bool
    {
        return in_array($this->status, [self::STATUS_COLLECTED, self::STATUS_RECEIVED], true);
    }
}