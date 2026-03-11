<?php

namespace App\Providers;

use App\Events\PaymentSuccessful;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionExpired;
use App\Listeners\HandlePaymentSuccessful;
use App\Listeners\HandleSubscriptionCreated;
use App\Listeners\HandleSubscriptionExpired;
use App\Models\Payment;
use App\Models\User;
use App\Observers\PaymentObserver;
use App\Policies\ParentChildPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Policy registrations
        Gate::policy(User::class, ParentChildPolicy::class);

        // Model observers
        Payment::observe(PaymentObserver::class);

        // Event → Listener bindings
        Event::listen(PaymentSuccessful::class,   HandlePaymentSuccessful::class);
        Event::listen(SubscriptionCreated::class, HandleSubscriptionCreated::class);
        Event::listen(SubscriptionExpired::class, HandleSubscriptionExpired::class);
    }
}
