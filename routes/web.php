<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\Learner\ModuleController as LearnerModuleController;
use App\Http\Controllers\Learner\LessonController as LearnerLessonController;
use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

// Homepage redirects to appropriate dashboard if logged in, otherwise shows learner login
// The actual learner login route is defined in routes/auth.php

// Public pages
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

// Profile completion routes (auth but no profile.completed middleware)
Route::middleware('auth')->group(function () {
    Route::get('/profile/complete', [ProfileCompletionController::class, 'show'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileCompletionController::class, 'store'])->name('profile.store');
});

// Certificate verification (public routes)
Route::get('/certificates/verify', [CertificateController::class, 'verifyForm'])->name('certificates.verify-form');
Route::post('/certificates/verify', [CertificateController::class, 'verify'])->name('certificates.verify');

// API routes for location data
Route::get('/api/cities/{provinceCode}', [LocationController::class, 'getCities']);
Route::get('/api/barangays/{cityCode}', [LocationController::class, 'getBarangays']);

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Learner profile edit (for updating learner-specific fields)
    Route::get('/profile/learner/edit', [ProfileCompletionController::class, 'edit'])->name('profile.learner.edit');
    Route::put('/profile/learner', [ProfileCompletionController::class, 'update'])->name('profile.learner.update');
    Route::put('/profile/password', [ProfileCompletionController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/account', [ProfileCompletionController::class, 'deleteAccount'])->name('profile.account.delete');

    // Subscription routes
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/upgrade', [SubscriptionController::class, 'processUpgrade'])->name('process-upgrade');
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe'); // New unified method
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/refund', [SubscriptionController::class, 'requestRefund'])->name('refund');
        Route::post('/renew', [SubscriptionController::class, 'renew'])->name('renew');
        Route::get('/status', [SubscriptionController::class, 'checkStatus'])->name('status');
    });

    // PayMongo Subscription Routes (Legacy - kept for backward compatibility)
    Route::prefix('subscribe')->name('subscribe.')->group(function () {
        Route::post('/monthly', [SubscriptionController::class, 'subscribeMonthly'])->name('monthly');
        Route::post('/annual', [SubscriptionController::class, 'subscribeAnnual'])->name('annual');
    });

    // Payment routes
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/create/{subscription}', [PaymentController::class, 'create'])->name('create');
        Route::post('/process/{subscription}', [PaymentController::class, 'process'])->name('process');
        Route::get('/pending/{payment}', [PaymentController::class, 'pending'])->name('pending');
        Route::get('/status/{payment}', [PaymentController::class, 'checkStatus'])->name('status');
        Route::get('/history', [PaymentController::class, 'history'])->name('history');
        Route::get('/receipt/{payment}', [PaymentController::class, 'receipt'])->name('receipt');
        
        // PayMongo automatic callbacks (triggers after payment completion)
        Route::get('/paymongo/success/{subscription}', [PaymentController::class, 'paymongoSuccess'])->name('paymongo.success');
        Route::get('/paymongo/failed/{subscription}', [PaymentController::class, 'paymongoFailed'])->name('paymongo.failed');
        
        // Legacy success/cancel pages
        Route::get('/success', function () {
            return view('payments.success');
        })->name('success');

        Route::get('/cancel', function () {
            // Load the first active paid plan from DB so the view shows real prices.
            $premiumPlan = \App\Models\SubscriptionPlan::where('is_active', true)
                ->where('price', '>', 0)
                ->orderBy('sort_order')
                ->first();
            return view('payments.cancel', compact('premiumPlan'));
        })->name('cancel');
        
        // Development only - simulate payment success (local environment only)
        if (app()->environment('local')) {
            Route::get('/simulate-success/{payment}', [PaymentController::class, 'simulateSuccess'])
                ->name('simulate-success');
        }
    });

    // Module routes (old - keeping for compatibility)
    Route::prefix('modules')->name('modules.')->middleware('profile.completed')->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('index');
        Route::get('/{module}', [ModuleController::class, 'show'])->name('show');
        Route::post('/{module}/enroll', [ModuleController::class, 'enroll'])->name('enroll');
        
        // Premium features - require premium middleware
        Route::middleware('premium')->group(function () {
            Route::get('/{module}/attachments', [ModuleController::class, 'attachments'])->name('attachments');
            Route::get('/attachments/{attachment}/download', [ModuleController::class, 'downloadAttachment'])->name('download-attachment');
        });
    });

    // Learner routes - new clean implementation
    Route::prefix('learn')->name('learner.')->middleware('profile.completed')->group(function () {
        // Module browsing and enrollment
        Route::get('/modules', [LearnerModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/{module}', [LearnerModuleController::class, 'show'])->name('modules.show');
        Route::post('/modules/{module}/enroll', [LearnerModuleController::class, 'enroll'])->name('modules.enroll');
        
        // Lesson viewing
        Route::get('/lessons/{lesson}', [LearnerLessonController::class, 'show'])->name('lessons.show');
        Route::post('/lessons/{lesson}/complete', [LearnerLessonController::class, 'complete'])->name('lessons.complete');
        
        // Topic completion
        Route::post('/topics/{topic}/complete', [LearnerLessonController::class, 'completeTopic'])->name('topics.complete');
        
        // Certificates (Premium only)
        Route::middleware('premium')->group(function () {
            Route::get('/certificates', [\App\Http\Controllers\Learner\CertificateController::class, 'index'])->name('certificates.index');
            Route::post('/modules/{module}/certificate', [\App\Http\Controllers\Learner\CertificateController::class, 'check'])->name('certificates.check');
            Route::get('/certificates/{certificate}', [\App\Http\Controllers\Learner\CertificateController::class, 'show'])->name('certificates.show');
            Route::get('/certificates/{certificate}/download', [\App\Http\Controllers\Learner\CertificateController::class, 'download'])->name('certificates.download');
        });
    });

    // Quiz routes
    Route::prefix('quizzes')->name('quizzes.')->group(function () {
        Route::get('/{quiz}', [QuizController::class, 'show'])->name('show');
        Route::get('/{quiz}/start', [QuizController::class, 'start'])->name('start');
        Route::post('/{quiz}/submit', [QuizController::class, 'submit'])->name('submit');
        Route::get('/attempts/{attempt}/result', [QuizController::class, 'result'])->name('result');
        Route::get('/history', [QuizController::class, 'history'])->name('history');
    });

    // Certificate routes (authenticated)
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [CertificateController::class, 'index'])->name('index');
        
        // Premium features
        Route::middleware('premium')->group(function () {
            Route::post('/generate/{module}', [CertificateController::class, 'generate'])->name('generate');
            Route::get('/{certificate}', [CertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/download', [CertificateController::class, 'download'])->name('download');
        });
    });

    // Learner Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['role:learner', 'profile.completed'])
        ->name('dashboard');

    // Instructor routes (Content Management)
    Route::prefix('instructor')->name('instructor.')->middleware('role:instructor')->group(function () {
        // Instructor Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Instructor\DashboardController::class, 'index'])->name('dashboard');
        
        // User Management (Learners only)
        Route::resource('users', \App\Http\Controllers\Instructor\UserController::class);
        
        // Module Management
        Route::resource('modules', \App\Http\Controllers\Instructor\ModuleController::class);
        
        // Lesson Management
        Route::resource('lessons', \App\Http\Controllers\Instructor\LessonController::class);
        Route::patch('lessons/{lesson}/move', [\App\Http\Controllers\Instructor\LessonController::class, 'move'])
            ->name('lessons.move');
        
        // Topic Management (Lesson Topics)
        Route::get('topics/create', [\App\Http\Controllers\Instructor\TopicController::class, 'create'])
            ->name('topics.create');
        Route::post('topics', [\App\Http\Controllers\Instructor\TopicController::class, 'store'])
            ->name('topics.store');
        Route::get('topics/{topic}/edit', [\App\Http\Controllers\Instructor\TopicController::class, 'edit'])
            ->name('topics.edit');
        Route::put('topics/{topic}', [\App\Http\Controllers\Instructor\TopicController::class, 'update'])
            ->name('topics.update');
        Route::delete('topics/{topic}', [\App\Http\Controllers\Instructor\TopicController::class, 'destroy'])
            ->name('topics.destroy');
        
        // Image upload for TinyMCE
        Route::post('upload/image', [\App\Http\Controllers\Instructor\TopicController::class, 'uploadImage'])
            ->name('upload.image');
        
        // Quiz Management
        Route::resource('quizzes', \App\Http\Controllers\Instructor\QuizManagementController::class);
        Route::get('quizzes/{quiz}/add-question', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'addQuestion'])
            ->name('quizzes.add-question');
        Route::post('quizzes/{quiz}/store-question', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'storeQuestion'])
            ->name('quizzes.store-question');
    });

    // Admin routes — restricted to users with the 'admin' role
    // Both 'auth' AND 'role:admin' are required.
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
        // Dashboard
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // User Management
        Route::resource('users', \App\Http\Controllers\Admin\UserAdminController::class);

        // UNIFIED Subscription & Plan Management
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'index'])->name('index');
            Route::post('/quick-action', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'quickAction'])->name('quick-action');
            Route::get('/create-plan', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'createPlan'])->name('create-plan');
            Route::post('/create-plan', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'storePlan'])->name('store-plan');
            Route::get('/subscription/{subscription}', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'showSubscription'])->name('show-subscription');
            Route::get('/plan/{subscriptionPlan}', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'showPlan'])->name('show-plan');
            Route::get('/plan/{subscriptionPlan}/edit', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'editPlan'])->name('edit-plan');
            Route::put('/plan/{subscriptionPlan}', [\App\Http\Controllers\Admin\UnifiedSubscriptionAdminController::class, 'updatePlan'])->name('update-plan');
        });

        // Subscription Plans (backward compatibility & advanced features)
        Route::prefix('subscription-plans')->name('subscription-plans.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'store'])->name('store');
            Route::get('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'show'])->name('show');
            Route::get('/{subscriptionPlan}/edit', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'edit'])->name('edit');
            Route::put('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'update'])->name('update');
            Route::delete('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'destroy'])->name('delete');
            Route::post('/{subscriptionPlan}/toggle', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'toggle'])->name('toggle');
            Route::post('/reorder', [\App\Http\Controllers\Admin\SubscriptionPlanAdminController::class, 'reorder'])->name('reorder');
        });

        // Payment Management
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PaymentAdminController::class, 'index'])->name('index');
            Route::get('/{payment}', [\App\Http\Controllers\Admin\PaymentAdminController::class, 'show'])->name('show');
            Route::post('/{payment}/refund', [\App\Http\Controllers\Admin\PaymentAdminController::class, 'processRefund'])->name('refund');
            Route::post('/{payment}/complete', [\App\Http\Controllers\Admin\PaymentAdminController::class, 'markAsCompleted'])->name('complete');
        });
    });
});

// PayMongo webhook — outside auth middleware.
// throttle:60,1  prevents abuse (max 60 requests per minute per IP).
// paymongo.webhook verifies the HMAC-SHA256 signature header before the controller runs.
Route::post('/webhook/paymongo', [PaymentController::class, 'webhook'])
    ->middleware(['throttle:60,1', 'paymongo.webhook'])
    ->name('webhook.paymongo');

require __DIR__.'/auth.php';
