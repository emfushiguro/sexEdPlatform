<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'plan',
        'status',
        'start_date',
        'end_date',
        'price_paid',
        'trial_ends_at',
        'cancelled_at',
        'cancellation_reason',
        'auto_renew',
        'grace_period_ends',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'trial_ends_at' => 'date',
            'cancelled_at' => 'datetime',
            'grace_period_ends' => 'datetime',
            'price_paid' => 'decimal:2',
            'auto_renew' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     * IMPORTANT: Do NOT put payment-completion or subscription-activation logic here.
     * The canonical flow is: payment → PaymentObserver → SubscriptionService::activate()
     * Adding shortcuts here causes SubscriptionCreated to be skipped (idempotent guard
     * fires early) so invoice generation and welcome email never queue.
     */
    protected static function booted()
    {
        // Only responsibility: clear the premium cache so isPremium() reflects reality instantly.
        static::updated(function (Subscription $subscription) {
            if ($subscription->isDirty('status')) {
                \Cache::forget("user.{$subscription->user_id}.is_premium");
            }
        });
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePremium($query)
    {
        return $query->where('plan', 'premium');
    }

    public function scopeExpiringSoon($query, $days = 3)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now());
    }

    /**
     * Scope for subscriptions that have passed their end date but still marked as active
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    public function scopeInGracePeriod($query)
    {
        return $query->where('status', 'past_due')
            ->where('grace_period_ends', '>', now());
    }

    // Helper Methods

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->end_date === null || $this->end_date->isAfter(now()));
    }

    public function isPremium(): bool
    {
        // Check if subscription is active first
        if (!$this->isActive()) {
            return false;
        }

        // If we have a plan relationship (new system), use it
        if ($this->plan_id) {
            return true; // Any active subscription with a plan_id is premium
        }

        // Legacy plan support (string-based plans)
        return in_array($this->plan, ['monthly', 'annual', 'premium']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function isInTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isInGracePeriod(): bool
    {
        return $this->isPastDue() && 
               $this->grace_period_ends && 
               $this->grace_period_ends->isFuture();
    }

    public function daysUntilExpiry(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getAmount(): float
    {
        // Use price_paid if available (set during subscription creation).
        if ($this->price_paid) {
            return (float) $this->price_paid;
        }

        // Fallback: read price from plan relationship
        if ($this->plan_id && $this->relationLoaded('plan')) {
            $planModel = $this->getRelation('plan');
            if ($planModel && isset($planModel->price)) {
                return (float) $planModel->price;
            }
        }

        return 0.00;
    }

    public function getPlanLabel(): string
    {
        // Check if we have a proper plan relationship (not the legacy string field)
        if ($this->plan_id && $this->relationLoaded('plan')) {
            $planModel = $this->getRelation('plan');
            if ($planModel && is_object($planModel) && isset($planModel->name)) {
                return $planModel->name ?? 'Unknown Plan';
            }
        }

        // Legacy plan support (string-based plans)
        return match($this->plan) {
            'monthly' => 'Monthly Premium',
            'annual'  => 'Annual Premium',
            'premium' => 'Premium Plan',
            default   => 'Free',
        };
    }

    public function canCancel(): bool
    {
        return $this->isActive() && !$this->isCancelled();
    }

    public function canRenew(): bool
    {
        return $this->isCancelled() && $this->end_date->isFuture();
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            'past_due' => 'Past Due',
            'pending' => 'Pending Activation',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'green',
            'cancelled' => 'gray',
            'expired' => 'red',
            'past_due' => 'orange',
            'pending' => 'yellow',
            default => 'gray',
        };
    }
}
