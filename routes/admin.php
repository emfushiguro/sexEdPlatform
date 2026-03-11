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
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // User Management
    Route::resource('users', Admin\UserAdminController::class);

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

    // Subscription Plans (backward compatibility & advanced features)
    Route::prefix('subscription-plans')->name('subscription-plans.')->group(function () {
        Route::get('/', [Admin\SubscriptionPlanAdminController::class, 'index'])->name('index');
        Route::get('/create', [Admin\SubscriptionPlanAdminController::class, 'create'])->name('create');
        Route::post('/', [Admin\SubscriptionPlanAdminController::class, 'store'])->name('store');
        Route::get('/{subscriptionPlan}', [Admin\SubscriptionPlanAdminController::class, 'show'])->name('show');
        Route::get('/{subscriptionPlan}/edit', [Admin\SubscriptionPlanAdminController::class, 'edit'])->name('edit');
        Route::put('/{subscriptionPlan}', [Admin\SubscriptionPlanAdminController::class, 'update'])->name('update');
        Route::delete('/{subscriptionPlan}', [Admin\SubscriptionPlanAdminController::class, 'destroy'])->name('delete');
        Route::post('/{subscriptionPlan}/toggle', [Admin\SubscriptionPlanAdminController::class, 'toggle'])->name('toggle');
        Route::post('/reorder', [Admin\SubscriptionPlanAdminController::class, 'reorder'])->name('reorder');
    });

    // Payment Management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [Admin\PaymentAdminController::class, 'index'])->name('index');
        Route::get('/{payment}', [Admin\PaymentAdminController::class, 'show'])->name('show');
        Route::post('/{payment}/refund', [Admin\PaymentAdminController::class, 'processRefund'])->name('refund');
        Route::post('/{payment}/complete', [Admin\PaymentAdminController::class, 'markAsCompleted'])->name('complete');
    });


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

    // Email Announcements
    Route::prefix('emails')->name('emails.')->group(function () {
        Route::get('/', fn() => view('admin.emails.index'))->name('index');
        Route::get('/compose', fn() => view('admin.emails.compose'))->name('compose');
    });

    // Organizations
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', fn() => view('admin.organizations.index'))->name('index');
        Route::get('/{id}', fn($id) => view('admin.organizations.show', ['id' => $id]))->name('show');
    });
});
