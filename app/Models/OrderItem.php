<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SAMPLE_COLLECTED = 'sample_collected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'order_id',
        'test_id',
        'price',
        'status',
        'assigned_to',
        'started_at',
        'due_at',
        'completed_at',
        'processing_notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'started_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }

    public function sample(): HasOne
    {
        return $this->hasOne(Sample::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->status !== self::STATUS_COMPLETED
            && now()->greaterThan($this->due_at);
    }
}