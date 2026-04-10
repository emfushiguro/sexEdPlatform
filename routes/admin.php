<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for the admin panel. All routes are prefixed with /admin
| and require both authentication and the admin role.
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Admin profile context
    Route::get('/profile', [Admin\AdminProfileController::class, 'show'])->name('profile.show');

    // Notifications
    Route::get('/notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [Admin\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/dropdown-open', [Admin\NotificationController::class, 'markDropdownRead'])->name('notifications.dropdown-open');
    Route::get('/notifications/{id}/read', [Admin\NotificationController::class, 'markRead'])->name('notifications.read');

    Route::prefix('content-reviews')->name('content-reviews.')->group(function () {
        Route::get('/', [Admin\ContentReviewController::class, 'index'])->name('index');
        Route::get('/{reviewRequest}', [Admin\ContentReviewController::class, 'show'])->name('show');
        Route::get('/{reviewRequest}/preview', Admin\ContentReviewPreviewController::class)->name('preview');
        Route::post('/{reviewRequest}/start-review', [Admin\ContentReviewController::class, 'startReview'])->name('start-review');
        Route::post('/{reviewRequest}/approve', [Admin\ContentReviewController::class, 'approve'])->name('approve');
        Route::post('/{reviewRequest}/reject', [Admin\ContentReviewController::class, 'reject'])->name('reject');
        Route::post('/{reviewRequest}/archive', [Admin\ContentReviewController::class, 'archive'])->name('archive');
        Route::post('/{reviewRequest}/penalty/confirm', [Admin\ContentReviewController::class, 'confirmPenalty'])->name('penalty.confirm');
    });

    Route::resource('modules', Admin\AdminModuleController::class)->except(['destroy']);

    // User management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [Admin\UserAdminController::class, 'index'])->name('index');
        Route::get('/create', [Admin\UserAdminController::class, 'create'])->name('create');
        Route::post('/', [Admin\UserAdminController::class, 'store'])->name('store');
        Route::get('/{user}', [Admin\UserAdminController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [Admin\UserAdminController::class, 'edit'])->name('edit');
        Route::put('/{user}', [Admin\UserAdminController::class, 'update'])->name('update');
        Route::patch('/{user}/status', [Admin\UserAdminController::class, 'updateStatus'])->name('status.update');
        Route::patch('/{user}/role', [Admin\UserAdminController::class, 'changeRole'])->name('role.update');

        Route::post('/relationships/attach', [Admin\UserAdminController::class, 'attachParentChild'])
            ->name('relationships.attach');
        Route::delete('/relationships/detach', [Admin\UserAdminController::class, 'detachParentChild'])
            ->name('relationships.detach');
        Route::patch('/relationships/verification', [Admin\UserAdminController::class, 'toggleParentChildVerification'])
            ->name('relationships.verification');

        Route::delete('/{user}', [Admin\UserAdminController::class, 'destroy'])->name('destroy');
    });

    // Subscriber management (subscription records)
    Route::prefix('subscribers')->name('subscribers.')->group(function () {
        Route::get('/', [Admin\SubscriberAdminController::class, 'index'])->name('index');
        Route::post('/quick-action', [Admin\SubscriberAdminController::class, 'quickAction'])->name('quick-action');

        // Plan management — static paths must come before the {subscription} wildcard
        Route::get('/create-plan', [Admin\UnifiedSubscriptionAdminController::class, 'createPlan'])->name('create-plan');
        Route::post('/create-plan', [Admin\UnifiedSubscriptionAdminController::class, 'storePlan'])->name('store-plan');
        Route::post('/quick-action-plan', [Admin\UnifiedSubscriptionAdminController::class, 'quickAction'])->name('quick-action-plan');
        Route::get('/plan/{subscriptionPlan}', [Admin\UnifiedSubscriptionAdminController::class, 'showPlan'])->name('show-plan');
        Route::get('/plan/{subscriptionPlan}/edit', [Admin\UnifiedSubscriptionAdminController::class, 'editPlan'])->name('edit-plan');
        Route::put('/plan/{subscriptionPlan}', [Admin\UnifiedSubscriptionAdminController::class, 'updatePlan'])->name('update-plan');

        // Wildcard route last so it doesn't swallow static paths
        Route::get('/{subscription}', [Admin\SubscriberAdminController::class, 'show'])->name('show');
    });

    // API endpoints for plan wizard
    Route::get('/api/features', [Admin\SubscriptionPlanAdminController::class, 'getFeatures'])->name('api.features');

    // Subscription Plans (backward compatibility & advanced features)
    Route::prefix('subscription-plans')->name('subscription-plans.')->group(function () {
        Route::get('/', [Admin\SubscriptionPlanAdminController::class, 'index'])->name('index');
        Route::get('/archived', [Admin\SubscriptionPlanAdminController::class, 'archived'])->name('archived');
        Route::get('/create', [Admin\SubscriptionPlanAdminController::class, 'create'])->name('create');
        Route::post('/', [Admin\SubscriptionPlanAdminController::class, 'store'])->name('store');
        Route::get('/{subscriptionPlan}', [Admin\SubscriptionPlanAdminController::class, 'show'])->name('show');
        Route::get('/{subscriptionPlan}/edit', [Admin\SubscriptionPlanAdminController::class, 'edit'])->name('edit');
        Route::put('/{subscriptionPlan}', [Admin\SubscriptionPlanAdminController::class, 'update'])->name('update');
        Route::delete('/{subscriptionPlan}', [Admin\SubscriptionPlanAdminController::class, 'destroy'])->name('delete');
        Route::post('/{subscriptionPlan}/toggle', [Admin\SubscriptionPlanAdminController::class, 'toggle'])->name('toggle');
        Route::get('/{subscriptionPlan}/impact', [Admin\SubscriptionPlanAdminController::class, 'impact'])->name('impact');
        Route::post('/{subscriptionPlan}/archive', [Admin\SubscriptionPlanAdminController::class, 'archive'])->name('archive');
        Route::post('/{subscriptionPlan}/restore', [Admin\SubscriptionPlanAdminController::class, 'restore'])->name('restore');
        Route::post('/reorder', [Admin\SubscriptionPlanAdminController::class, 'reorder'])->name('reorder');
    });

    // Payment Management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [Admin\PaymentAdminController::class, 'index'])->name('index');
        Route::get('/{payment}', [Admin\PaymentAdminController::class, 'show'])->name('show');
        Route::get('/{payment}/receipt', [Admin\PaymentAdminController::class, 'receipt'])->name('receipt');
        Route::post('/{payment}/archive', [Admin\PaymentAdminController::class, 'archive'])->name('archive');
        Route::delete('/{payment}', [Admin\PaymentAdminController::class, 'destroy'])->name('destroy');
    });

    // Monetization
    Route::prefix('monetization')->name('monetization.')->group(function () {
        Route::get('/commission-settings', [Admin\CommissionSettingsController::class, 'index'])
            ->name('commission-settings.index');
        Route::post('/commission-settings', [Admin\CommissionSettingsController::class, 'store'])
            ->name('commission-settings.store');
        Route::put('/commission-settings/{commissionPolicy}', [Admin\CommissionSettingsController::class, 'update'])
            ->name('commission-settings.update');
        Route::get('/module-revenue', [Admin\ModuleRevenueController::class, 'index'])
            ->name('module-revenue.index');
        Route::patch('/module-revenue/{moduleSaleLedger}/payout-status', [Admin\ModuleRevenueController::class, 'updatePayoutStatus'])
            ->name('module-revenue.payout.update');
    });

    Route::prefix('instructor-applications')->name('instructor-applications.')->group(function () {
        Route::get('/', [Admin\InstructorApplicationController::class, 'index'])->name('index');
        Route::get('/{application}', [Admin\InstructorApplicationController::class, 'show'])->name('show');
        Route::post('/{application}/approve', [Admin\InstructorApplicationController::class, 'approve'])->name('approve');
        Route::post('/{application}/reject', [Admin\InstructorApplicationController::class, 'reject'])->name('reject');
        Route::post('/{application}/archive', [Admin\InstructorApplicationController::class, 'archive'])->name('archive');
        Route::delete('/{application}', [Admin\InstructorApplicationController::class, 'destroy'])->name('destroy');
    });

    Route::post('/subscribers/{subscription}/archive', [Admin\SubscriberAdminController::class, 'archive'])
        ->name('subscribers.archive');
    Route::delete('/subscribers/{subscription}', [Admin\SubscriberAdminController::class, 'destroy'])
        ->name('subscribers.destroy');


    // Calendar
    Route::get('/calendar', fn() => view('admin.calendar.index'))->name('calendar.index');

    // Seminars
    Route::prefix('seminars')->name('seminars.')->group(function () {
        Route::get('/', fn() => view('admin.seminars.index'))->name('index');
        Route::get('/create', fn() => view('admin.seminars.create'))->name('create');
        Route::get('/{id}', fn($id) => view('admin.seminars.show', ['id' => $id]))->name('show');
    });

    // Messages
    Route::get('/messages', fn() => view('admin.messages.index'))->name('messages.index');

    // Organizations
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', fn() => view('admin.organizations.index'))->name('index');
        Route::get('/{id}', fn($id) => view('admin.organizations.show', ['id' => $id]))->name('show');
    });
});
