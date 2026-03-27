<?php

namespace App\Providers;

use App\Events\PaymentSuccessful;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionExpired;
use App\Listeners\HandlePaymentSuccessful;
use App\Listeners\HandleSubscriptionCreated;
use App\Listeners\HandleSubscriptionExpired;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Observers\PaymentObserver;
use App\Policies\ParentChildPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, ParentChildPolicy::class);

        Payment::observe(PaymentObserver::class);

        Event::listen(PaymentSuccessful::class, HandlePaymentSuccessful::class);
        Event::listen(SubscriptionCreated::class, HandleSubscriptionCreated::class);
        Event::listen(SubscriptionExpired::class, HandleSubscriptionExpired::class);

        View::composer('layouts.admin', function ($view): void {
            $notificationItems = collect([
                [
                    'label' => 'Pending payments',
                    'value' => Payment::query()->whereIn('status', ['pending', 'processing'])->count(),
                    'href' => route('admin.payments.index', ['status' => 'pending']),
                    'tone' => 'amber',
                    'message' => 'Payments waiting for review or reconciliation.',
                ],
                [
                    'label' => 'Subscriptions expiring soon',
                    'value' => Subscription::query()->expiringSoon()->count(),
                    'href' => route('admin.subscribers.index'),
                    'tone' => 'blue',
                    'message' => 'Active subscribers nearing the end of their access window.',
                ],
                [
                    'label' => 'Inactive plans',
                    'value' => SubscriptionPlan::query()->notArchived()->where('is_active', false)->count(),
                    'href' => route('admin.subscription-plans.index'),
                    'tone' => 'rose',
                    'message' => 'Plans saved in admin but not currently visible to learners.',
                ],
            ]);

            $view->with('adminNotifications', [
                'items' => $notificationItems->all(),
                'unread_count' => (int) $notificationItems->sum('value'),
            ]);
        });
    }
}
