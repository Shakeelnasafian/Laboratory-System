<?php

namespace App\Models;

use App\Traits\BelongsToLab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use BelongsToLab;

    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_UNPAID = 'unpaid';

    public const STATUSES = [
        self::STATUS_PAID => 'Paid',
        self::STATUS_PARTIAL => 'Partial',
        self::STATUS_UNPAID => 'Unpaid',
    ];

    protected $fillable = [
        'lab_id', 'order_id', 'invoice_number', 'subtotal',
        'discount', 'total', 'paid_amount', 'balance',
        'payment_status', 'payment_method', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $date = now()->format('Ymd');

                $count = DB::transaction(function () use ($invoice) {
                    // Lock a real row to serialize invoice numbering for this lab.
                    // PostgreSQL does not support FOR UPDATE on aggregate COUNT queries.
                    DB::table('labs')
                        ->where('id', $invoice->lab_id)
                        ->lockForUpdate()
                        ->first();

                    return static::withoutGlobalScope('lab')
                        ->where('lab_id', $invoice->lab_id)
                        ->whereDate('created_at', today())
                        ->count() + 1;
                });

                $invoice->invoice_number = 'INV-' . $date . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
