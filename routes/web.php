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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Landing page
Route::view('/', 'landing')->name('home');
Route::view('/landing', 'landing');

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
        Route::get('/modules', [LearnerModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/{module}', [LearnerModuleController::class, 'show'])->name('modules.show');
        Route::post('/modules/{module}/enroll', [LearnerModuleController::class, 'enroll'])->name('modules.enroll');

        Route::get('/lessons/{lesson}', [LearnerLessonController::class, 'show'])->name('lessons.show');
        Route::post('/lessons/{lesson}/complete', [LearnerLessonController::class, 'complete'])->name('lessons.complete');
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
});

// PayMongo webhook — outside auth middleware.
Route::post('/webhook/paymongo', [\App\Http\Controllers\Api\WebhookController::class, 'paymongo'])
    ->middleware(['throttle:60,1', 'paymongo.webhook'])
    ->name('webhook.paymongo');

require __DIR__.'/auth.php';
