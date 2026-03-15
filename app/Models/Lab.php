<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lab extends Model
{
    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'city',
        'logo', 'license_number', 'owner_name',
        'header_text', 'footer_text', 'is_active', 'subscription_expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_expires_at' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_expires_at !== null && $this->subscription_expires_at->isPast();
    }

    public function subscriptionDaysLeft(): int
    {
        if (! $this->subscription_expires_at) {
            return PHP_INT_MAX;
        }

        return max(0, (int) now()->diffInDays($this->subscription_expires_at, false));
    }
}
