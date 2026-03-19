<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Learner\ProfileCompletionController;
use App\Http\Controllers\Learner\SubscriptionController;
use App\Http\Controllers\Learner\QuizController;
use App\Http\Controllers\Learner\ModuleController as LearnerModuleController;
use App\Http\Controllers\Learner\LessonController as LearnerLessonController;
use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Default entry point — landing page for guests, dashboard for authenticated users
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/learn/dashboard');
    }
    return view('landing.index');
})->name('home');

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

// Certificate verification (public)
Route::get('/certificates/verify', [CertificateController::class, 'verifyForm'])->name('certificates.verify-form');
Route::post('/certificates/verify', [CertificateController::class, 'verify'])->name('certificates.verify');

/*
|--------------------------------------------------------------------------
| Profile Completion (auth, no profile.completed middleware)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile/complete', [ProfileCompletionController::class, 'show'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileCompletionController::class, 'store'])->name('profile.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Learner profile edit
    Route::get('/profile/learner/edit', [ProfileCompletionController::class, 'edit'])->name('profile.learner.edit');
    Route::put('/profile/learner', [ProfileCompletionController::class, 'update'])->name('profile.learner.update');
    Route::put('/profile/password', [ProfileCompletionController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/account', [ProfileCompletionController::class, 'deleteAccount'])->name('profile.account.delete');

    // Subscription routes
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
        Route::post('/upgrade', [SubscriptionController::class, 'processUpgrade'])->name('process-upgrade');
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');
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

        // PayMongo automatic callbacks
        Route::get('/paymongo/success/{subscription}', [PaymentController::class, 'paymongoSuccess'])->name('paymongo.success');
        Route::get('/paymongo/failed/{subscription}', [PaymentController::class, 'paymongoFailed'])->name('paymongo.failed');

        // Success/cancel pages
        Route::get('/success', [PaymentController::class, 'success'])->name('success');
        Route::get('/cancel', [PaymentController::class, 'cancel'])->name('cancel');

        // Development only - simulate payment success
        if (app()->environment('local')) {
            Route::get('/simulate-success/{payment}', [PaymentController::class, 'simulateSuccess'])
                ->name('simulate-success');
        }
    });

    // Learner routes
    Route::prefix('learn')->name('learner.')->middleware('profile.completed')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Learner\DashboardController::class, 'index'])->name('dashboard');

        // Live search (AJAX)
        Route::get('/search', [\App\Http\Controllers\Learner\SearchController::class, 'index'])->name('search');

        // Notifications
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Learner\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::get('/notifications/{id}/read', [\App\Http\Controllers\Learner\NotificationController::class, 'markRead'])->name('notifications.read');

        // Module browsing and enrollment
        Route::get('/modules', [LearnerModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/{module}', [LearnerModuleController::class, 'show'])->name('modules.show');
        Route::post('/modules/{module}/enroll', [LearnerModuleController::class, 'enroll'])->name('modules.enroll');

        Route::get('/lessons/{lesson}', [LearnerLessonController::class, 'show'])->name('lessons.show');
        Route::post('/lessons/{lesson}/complete', [LearnerLessonController::class, 'complete'])->name('lessons.complete');
        Route::post('/topics/{topic}/complete', [LearnerLessonController::class, 'completeTopic'])->name('topics.complete');
        Route::post('/topics/{topic}/uncomplete', [LearnerLessonController::class, 'uncompleteTopic'])->name('topics.uncomplete');
        Route::post('/lessons/topics/{topic}/complete', [LearnerLessonController::class, 'completeTopic'])->name('lessons.topics.complete');

        // Shields and streak savers
        Route::post('/shields/refill', [\App\Http\Controllers\Learner\ShieldRefillController::class, 'store'])->name('shields.refill');
        Route::post('/streak-savers/buy', [\App\Http\Controllers\Learner\StreakSaverController::class, 'store'])->name('streak-savers.buy');

        // Gamification rules page
        Route::get('/gamification', [\App\Http\Controllers\Learner\GamificationController::class, 'rules'])->name('gamification');

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

        Route::middleware('premium')->group(function () {
            Route::post('/generate/{module}', [CertificateController::class, 'generate'])->name('generate');
            Route::get('/{certificate}', [CertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/download', [CertificateController::class, 'download'])->name('download');
        });
    });

    // Parent monitoring routes
    Route::prefix('parent')->name('parent.')->middleware('verified')->group(function () {
        Route::get('/children/{child}', [\App\Http\Controllers\ParentController::class, 'show'])
            ->name('children.show');
        Route::post('/children/{child}/enrollments/{enrollment}/approve', [\App\Http\Controllers\ParentController::class, 'approveEnrollment'])
            ->name('children.enrollments.approve');
        Route::post('/children/{child}/enrollments/{enrollment}/reject', [\App\Http\Controllers\ParentController::class, 'rejectEnrollment'])
            ->name('children.enrollments.reject');
    });

    // Instructor routes (Content Management)
    Route::prefix('instructor')->name('instructor.')->middleware('role:instructor')->group(function () {
        // Instructor Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Instructor\DashboardController::class, 'index'])->name('dashboard');
        
        // Learner Management (view-only)
        Route::resource('users', \App\Http\Controllers\Instructor\UserController::class)->only(['index', 'show']);
        
        // Module Management
        Route::resource('modules', \App\Http\Controllers\Instructor\ModuleController::class);
        Route::patch('modules/{module}/activate', [\App\Http\Controllers\Instructor\ModuleController::class, 'activate'])->name('modules.activate');
        Route::patch('modules/{module}/deactivate', [\App\Http\Controllers\Instructor\ModuleController::class, 'deactivate'])->name('modules.deactivate');
        Route::patch('modules/{id}/restore', [\App\Http\Controllers\Instructor\ModuleController::class, 'restore'])->name('modules.restore');
        
        // Enrollment Management
        Route::get('enrollments', [\App\Http\Controllers\Instructor\EnrollmentController::class, 'index'])
            ->name('enrollments.index');
        Route::get('enrollments/{enrollment}', [\App\Http\Controllers\Instructor\EnrollmentController::class, 'show'])
            ->name('enrollments.show');
        Route::patch('enrollments/{enrollment}/approve', [\App\Http\Controllers\Instructor\EnrollmentController::class, 'approve'])
            ->name('enrollments.approve');
        Route::patch('enrollments/{enrollment}/reject', [\App\Http\Controllers\Instructor\EnrollmentController::class, 'reject'])
            ->name('enrollments.reject');
        Route::get('modules/{module}/enrollments', [\App\Http\Controllers\Instructor\EnrollmentController::class, 'moduleEnrollments'])
            ->name('modules.enrollments');
        
        // Lesson Management
        Route::patch('lessons/reorder', [\App\Http\Controllers\Instructor\LessonController::class, 'reorder'])->name('lessons.reorder');
        Route::resource('lessons', \App\Http\Controllers\Instructor\LessonController::class);
        Route::patch('lessons/{lesson}/move', [\App\Http\Controllers\Instructor\LessonController::class, 'move'])
            ->name('lessons.move');
        
        // Topic Management (Lesson Topics)
        Route::patch('topics/reorder', [\App\Http\Controllers\Instructor\TopicController::class, 'reorder'])->name('topics.reorder');
        Route::get('topics/create', [\App\Http\Controllers\Instructor\TopicController::class, 'create'])
            ->name('topics.create');
        Route::post('topics', [\App\Http\Controllers\Instructor\TopicController::class, 'store'])
            ->name('topics.store');
        Route::get('topics/{topic}/edit', [\App\Http\Controllers\Instructor\TopicController::class, 'edit'])
            ->name('topics.edit');
        Route::get('topics/{topic}/preview', [\App\Http\Controllers\Instructor\TopicController::class, 'preview'])
            ->name('topics.preview');
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
        Route::get('quizzes/{quiz}/questions/{question}/edit', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'editQuestion'])
            ->name('quizzes.edit-question');
        Route::put('quizzes/{quiz}/questions/{question}', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'updateQuestion'])
            ->name('quizzes.update-question');
        Route::delete('quizzes/{quiz}/questions/{question}', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'deleteQuestion'])
            ->name('quizzes.delete-question');
        
        // CSV Import
        Route::get('quizzes/{quiz}/import/template', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'downloadTemplate'])
            ->name('quizzes.import.template');
        Route::post('quizzes/{quiz}/import/preview', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'previewImport'])
            ->name('quizzes.import.preview');
        Route::post('quizzes/{quiz}/import/confirm', [\App\Http\Controllers\Instructor\QuizManagementController::class, 'confirmImport'])
            ->name('quizzes.import.confirm');
        
        // Image Library
        Route::get('image-library', [\App\Http\Controllers\Instructor\ImageLibraryController::class, 'index'])
            ->name('image-library.index');
        Route::get('image-library/json', [\App\Http\Controllers\Instructor\ImageLibraryController::class, 'indexJson'])
            ->name('image-library.json');
        Route::post('image-library/upload', [\App\Http\Controllers\Instructor\ImageLibraryController::class, 'upload'])
            ->name('image-library.upload');
        Route::delete('image-library/{filename}', [\App\Http\Controllers\Instructor\ImageLibraryController::class, 'delete'])
            ->name('image-library.delete');
    });

    // Admin routes (System Management) - TODO: To be built
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', function() {
            return view('admin.dashboard');
        })->name('dashboard');
        
        // TODO: Subscription management routes
        // TODO: User management routes  
        // TODO: Platform settings routes
    });
});

// Paymongo webhook (outside auth middleware)
Route::post('/webhook/paymongo', [PaymentController::class, 'webhook'])->name('webhook.paymongo');

require __DIR__.'/auth.php';
