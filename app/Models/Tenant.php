<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_name',
        'business_type',
        'subscription_status',
        'subscription_plan',
        'billing_cycle',
        'trial_ends_at',
        'subscription_starts_at',
        'subscription_expires_at',
        'max_outlets',
        'max_users',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_starts_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'max_outlets' => 'integer',
            'max_users' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Check if tenant is currently on valid free trial.
     */
    public function isTrialActive(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if free trial has expired.
     */
    public function isTrialExpired(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    /**
     * Get remaining days in free trial.
     */
    public function remainingTrialDays(): int
    {
        if (! $this->isTrialActive() || ! $this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Check if tenant has active paid subscription.
     */
    public function isSubscriptionActive(): bool
    {
        return $this->subscription_status === 'active'
            && (is_null($this->subscription_expires_at) || $this->subscription_expires_at->isFuture());
    }

    /**
     * Check if tenant has valid access (trial or active subscription).
     */
    public function hasValidAccess(): bool
    {
        return $this->isSubscriptionActive() || $this->isTrialActive();
    }

    /**
     * Check if tenant is required to pay subscription to continue using POS.
     */
    public function requiresPayment(): bool
    {
        return ! $this->hasValidAccess();
    }
}
