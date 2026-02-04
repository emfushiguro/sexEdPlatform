<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\Learner\ModuleController as LearnerModuleController;
use App\Http\Controllers\Learner\LessonController as LearnerLessonController;
use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;


// Homepage - Learner Login (redirect to dashboard if already logged in)
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('learner')) {
            return redirect()->route('dashboard');
        } else {
            Auth::logout();
            return redirect()->route('home')->withErrors(['email' => 'User does not have the right roles.']);
        }
    }
    return view('auth.learner-login');
})->name('home');

// Profile completion routes (auth but no profile.completed middleware)
Route::middleware('auth')->group(function () {
    Route::get('/profile/complete', [ProfileCompletionController::class, 'show'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileCompletionController::class, 'store'])->name('profile.store');
});

// Certificate verification (public routes)
Route::get('/certificates/verify', [CertificateController::class, 'verifyForm'])->name('certificates.verify-form');
Route::post('/certificates/verify', [CertificateController::class, 'verify'])->name('certificates.verify');

// API route for loading barangays by city
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
        Route::get('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/cancel', [SubscriptionController::class, 'processCancel'])->name('process-cancel');
        Route::post('/renew', [SubscriptionController::class, 'renew'])->name('renew');
        Route::get('/status', [SubscriptionController::class, 'checkStatus'])->name('status');
    });

    // Payment routes
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/create/{subscription}', [PaymentController::class, 'create'])->name('create');
        Route::post('/process/{subscription}', [PaymentController::class, 'process'])->name('process');
        Route::get('/pending/{payment}', [PaymentController::class, 'pending'])->name('pending');
        Route::get('/history', [PaymentController::class, 'history'])->name('history');
        Route::get('/receipt/{payment}', [PaymentController::class, 'receipt'])->name('receipt');
        
        // Development only - simulate payment success
        Route::get('/simulate-success/{payment}', [PaymentController::class, 'simulateSuccess'])
            ->name('simulate-success');
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
});

// Health Centers - Public routes (accessible to authenticated users)
Route::middleware('auth')->prefix('health-centers')->name('health-centers.')->group(function () {
    Route::get('/', [App\Http\Controllers\HealthCenterController::class, 'index'])->name('index');
    Route::get('/{clinic}', [App\Http\Controllers\HealthCenterController::class, 'show'])->name('show');
});

Route::middleware('auth')->group(function () {
    // Admin routes (role-based access)
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        // Admin Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        
        // User Management
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        
        // Module Management
        Route::resource('modules', \App\Http\Controllers\Admin\ModuleController::class);
        
        // Lesson Management
        Route::resource('lessons', \App\Http\Controllers\Admin\LessonController::class);
        Route::patch('lessons/{lesson}/move', [\App\Http\Controllers\Admin\LessonController::class, 'move'])
            ->name('lessons.move');
        
        // Quiz Management
        Route::resource('quizzes', \App\Http\Controllers\Admin\QuizManagementController::class);
        Route::get('quizzes/{quiz}/add-question', [\App\Http\Controllers\Admin\QuizManagementController::class, 'addQuestion'])
            ->name('quizzes.add-question');
        Route::post('quizzes/{quiz}/store-question', [\App\Http\Controllers\Admin\QuizManagementController::class, 'storeQuestion'])
            ->name('quizzes.store-question');

        // Clinic Management
        Route::resource('clinics', ClinicController::class);
        Route::get('clinics/analytics', [ClinicController::class, 'analytics'])->name('clinics.analytics');
        Route::patch('clinics/{clinic}/approve', [ClinicController::class, 'approve'])->name('clinics.approve');
        Route::patch('clinics/{clinic}/reject', [ClinicController::class, 'reject'])->name('clinics.reject');
        Route::patch('clinics/{clinic}/toggle-active', [ClinicController::class, 'toggleActive'])->name('clinics.toggle-active');
        Route::patch('clinics/{clinic}/toggle-verified', [ClinicController::class, 'toggleVerified'])->name('clinics.toggle-verified');
        Route::post('clinics/bulk-approve', [ClinicController::class, 'bulkApprove'])->name('clinics.bulk-approve');
        Route::post('clinics/bulk-reject', [ClinicController::class, 'bulkReject'])->name('clinics.bulk-reject');
    });
});

// Paymongo webhook (outside auth middleware)
Route::post('/webhook/paymongo', [PaymentController::class, 'webhook'])->name('webhook.paymongo');

require __DIR__.'/auth.php';
