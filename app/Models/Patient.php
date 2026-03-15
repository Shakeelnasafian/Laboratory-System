<?php

namespace App\Models;

use App\Traits\BelongsToLab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Patient extends Model
{
    use BelongsToLab, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'cnic', 'phone', 'email', 'gender', 'dob', 'age', 'age_unit'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'lab_id', 'patient_id', 'name', 'cnic', 'phone', 'email',
        'gender', 'dob', 'age', 'age_unit', 'address', 'referred_by',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($patient) {
            if (empty($patient->patient_id)) {
                $last = static::withoutGlobalScope('lab')
                    ->where('lab_id', $patient->lab_id)
                    ->latest('id')->first();
                $patient->patient_id = 'P-' . str_pad(($last ? $last->id + 1 : 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
