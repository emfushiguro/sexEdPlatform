<?php

namespace App\Providers;

use App\Events\PaymentSuccessful;
use App\Events\Chat\MessageSent as ChatMessageSent;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionExpired;
use App\Listeners\HandlePaymentSuccessful;
use App\Listeners\Chat\SendInAppChatMessageNotification;
use App\Listeners\HandleSubscriptionCreated;
use App\Listeners\HandleSubscriptionExpired;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\InstructorProfile;
use App\Models\InstructorApplication;
use App\Models\ModuleReviewRequest;
use App\Models\User;
use App\Observers\PaymentObserver;
use App\Policies\InstructorProfilePolicy;
use App\Policies\ParentChildPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('chat-messages', function (Request $request) {
            $userId = (int) ($request->user()?->id ?? 0);
            $conversationRouteParam = $request->route('conversation');

            $conversationId = is_object($conversationRouteParam)
                ? (int) ($conversationRouteParam->id ?? 0)
                : (int) $conversationRouteParam;

            $key = "chat-message:{$userId}:{$conversationId}";

            return Limit::perSecond(10, 10)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'You are sending messages too quickly. Please wait a moment.',
                    ], 429, $headers);
                });
        });

        Gate::policy(User::class, ParentChildPolicy::class);
        Gate::policy(InstructorProfile::class, InstructorProfilePolicy::class);

        Payment::observe(PaymentObserver::class);

        Event::listen(PaymentSuccessful::class, HandlePaymentSuccessful::class);
        Event::listen(SubscriptionCreated::class, HandleSubscriptionCreated::class);
        Event::listen(SubscriptionExpired::class, HandleSubscriptionExpired::class);
        Event::listen(ChatMessageSent::class, SendInAppChatMessageNotification::class);

        View::composer('layouts.admin', function ($view): void {
            $moderationCounts = [
                'pending_instructor_applications' => InstructorApplication::query()->where('status', 'pending')->count(),
                'pending_module_reviews' => ModuleReviewRequest::query()->where('status', 'in_review')->count(),
            ];

            $operationalSignalItems = collect([
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

            /** @var User|null $adminUser */
            $adminUser = Auth::user();
            $adminRecentNotifications = collect();
            $adminDbUnreadCount = 0;

            if ($adminUser && $adminUser->role === 'admin') {
                $adminRecentNotifications = $adminUser->notifications()->latest()->limit(8)->get();
                $adminDbUnreadCount = $adminUser->unreadNotifications()->count();
            }

            $view->with('adminNotifications', [
                'items' => $adminRecentNotifications,
                'unread_count' => $adminDbUnreadCount,
            ]);
            $view->with('adminOperationalSignals', [
                'items' => $operationalSignalItems->all(),
                'unread_count' => (int) $operationalSignalItems->sum('value'),
            ]);
            $view->with('adminModerationCounts', $moderationCounts);
        });
    }
}
