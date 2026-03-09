<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultRevision extends Model
{
    protected $fillable = [
        'result_id',
        'revised_by',
        'previous_value',
        'previous_unit',
        'previous_normal_range',
        'previous_is_abnormal',
        'previous_flag',
        'previous_remarks',
        'previous_status',
        'revised_at',
    ];

    protected $casts = [
        'previous_is_abnormal' => 'boolean',
        'revised_at' => 'datetime',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}