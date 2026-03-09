<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_LAB_ADMIN = 'lab_admin';
    public const ROLE_LAB_INCHARGE = 'lab_incharge';
    public const ROLE_RECEPTIONIST = 'receptionist';
    public const ROLE_TECHNICIAN = 'technician';

    protected $fillable = [
        'lab_id', 'name', 'email', 'phone', 'password', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPERADMIN);
    }

    public function isLabAdmin(): bool
    {
        return $this->hasRole(self::ROLE_LAB_ADMIN);
    }

    public function canCollectSamples(): bool
    {
        return $this->hasAnyRole([self::ROLE_LAB_ADMIN, self::ROLE_LAB_INCHARGE, self::ROLE_RECEPTIONIST]);
    }

    public function canReceiveSamples(): bool
    {
        return $this->hasAnyRole([self::ROLE_LAB_ADMIN, self::ROLE_LAB_INCHARGE, self::ROLE_TECHNICIAN]);
    }

    public function canWorkBench(): bool
    {
        return $this->hasAnyRole([self::ROLE_LAB_ADMIN, self::ROLE_LAB_INCHARGE, self::ROLE_TECHNICIAN]);
    }

    public function canVerifyResults(): bool
    {
        return $this->hasAnyRole([self::ROLE_LAB_ADMIN, self::ROLE_LAB_INCHARGE]);
    }

    public function canReleaseResults(): bool
    {
        return $this->canVerifyResults();
    }
}