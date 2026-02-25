<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'method',
        'status',
        'transaction_id',
        'payment_details',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payment_details' => 'array',
        ];
    }

    /**
     * Boot the model.
     * NOTE: activation on payment completion is handled exclusively by PaymentObserver
     * (app/Observers/PaymentObserver.php) which calls SubscriptionService::activate().
     * Do NOT add subscription-activation logic here — it would run before PaymentObserver
     * and cause the SubscriptionCreated event to be skipped (idempotency guard fires early).
     */
    protected static function booted()
    {
        // Intentionally empty — see PaymentObserver for payment lifecycle hooks.
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helper Methods

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted(string $transactionId = null): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }
}
