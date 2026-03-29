<?php

use App\Http\Controllers\Instructor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Instructor Routes
|--------------------------------------------------------------------------
|
| Routes for instructor-facing content management features.
| All routes are prefixed with /instructor and require the instructor role.
|
*/

Route::prefix('instructor')->name('instructor.')->middleware(['auth', 'role:instructor'])->group(function () {
    // Instructor Dashboard
    Route::get('/dashboard', [Instructor\DashboardController::class, 'index'])->name('dashboard');

    // Search endpoint
    Route::get('/search', [Instructor\SearchController::class, 'index'])->name('search');

    // Assessment Insights
    Route::get('/assessments', [Instructor\AssessmentLogController::class, 'index'])
        ->name('assessments.index');

    // Instructor Profile
    Route::get('/profile', [Instructor\ProfileController::class, 'show'])
        ->name('profile.show');
    Route::get('/profile/edit', [Instructor\ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::put('/profile', [Instructor\ProfileController::class, 'update'])
        ->name('profile.update');

    // Learner Management (view-only)
    Route::resource('users', Instructor\UserController::class)->only(['index', 'show']);

    // Module Management
    Route::resource('modules', Instructor\ModuleController::class);
    Route::patch('modules/{module}/activate', [Instructor\ModuleController::class, 'activate'])
        ->name('modules.activate');
    Route::patch('modules/{module}/deactivate', [Instructor\ModuleController::class, 'deactivate'])
        ->name('modules.deactivate');
    Route::patch('modules/{id}/restore', [Instructor\ModuleController::class, 'restore'])
        ->name('modules.restore');

    // Enrollment Management
    Route::get('enrollments', [Instructor\EnrollmentController::class, 'index'])
        ->name('enrollments.index');
    Route::get('enrollments/{enrollment}', [Instructor\EnrollmentController::class, 'show'])
        ->name('enrollments.show');
    Route::patch('enrollments/{enrollment}/approve', [Instructor\EnrollmentController::class, 'approve'])
        ->name('enrollments.approve');
    Route::patch('enrollments/{enrollment}/reject', [Instructor\EnrollmentController::class, 'reject'])
        ->name('enrollments.reject');
    Route::get('modules/{module}/enrollments', [Instructor\EnrollmentController::class, 'moduleEnrollments'])
        ->name('modules.enrollments');

    // Lesson Management
    Route::patch('lessons/reorder', [Instructor\LessonController::class, 'reorder'])
        ->name('lessons.reorder');
    Route::resource('lessons', Instructor\LessonController::class);
    Route::patch('lessons/{lesson}/move', [Instructor\LessonController::class, 'move'])
        ->name('lessons.move');

    // Topic Management (Lesson Topics)
    Route::patch('topics/reorder', [Instructor\TopicController::class, 'reorder'])
        ->name('topics.reorder');
    Route::get('topics/create', [Instructor\TopicController::class, 'create'])
        ->name('topics.create');
    Route::post('topics', [Instructor\TopicController::class, 'store'])
        ->name('topics.store');
    Route::get('topics/{topic}/edit', [Instructor\TopicController::class, 'edit'])
        ->name('topics.edit');
    Route::get('topics/{topic}/preview', [Instructor\TopicController::class, 'preview'])
        ->name('topics.preview');
    Route::put('topics/{topic}', [Instructor\TopicController::class, 'update'])
        ->name('topics.update');
    Route::delete('topics/{topic}', [Instructor\TopicController::class, 'destroy'])
        ->name('topics.destroy');

    // Image upload for TinyMCE
    Route::post('upload/image', [Instructor\TopicController::class, 'uploadImage'])
        ->name('upload.image');

    // Quiz Management
    Route::resource('quizzes', Instructor\QuizManagementController::class);
    Route::get('quizzes/{quiz}/add-question', [Instructor\QuizManagementController::class, 'addQuestion'])
        ->name('quizzes.add-question');
    Route::post('quizzes/{quiz}/store-question', [Instructor\QuizManagementController::class, 'storeQuestion'])
        ->name('quizzes.store-question');
    Route::get('quizzes/{quiz}/questions/{question}/edit', [Instructor\QuizManagementController::class, 'editQuestion'])
        ->name('quizzes.edit-question');
    Route::put('quizzes/{quiz}/questions/{question}', [Instructor\QuizManagementController::class, 'updateQuestion'])
        ->name('quizzes.update-question');
    Route::delete('quizzes/{quiz}/questions/{question}', [Instructor\QuizManagementController::class, 'deleteQuestion'])
        ->name('quizzes.delete-question');

    // CSV Import
    Route::get('quizzes/{quiz}/import/template', [Instructor\QuizManagementController::class, 'downloadTemplate'])
        ->name('quizzes.import.template');
    Route::post('quizzes/{quiz}/import/preview', [Instructor\QuizManagementController::class, 'previewImport'])
        ->name('quizzes.import.preview');
    Route::post('quizzes/{quiz}/import/confirm', [Instructor\QuizManagementController::class, 'confirmImport'])
        ->name('quizzes.import.confirm');

    // Image Library
    Route::get('image-library', [Instructor\ImageLibraryController::class, 'index'])
        ->name('image-library.index');
    Route::get('image-library/json', [Instructor\ImageLibraryController::class, 'indexJson'])
        ->name('image-library.json');
    Route::post('image-library/upload', [Instructor\ImageLibraryController::class, 'upload'])
        ->name('image-library.upload');
    Route::delete('image-library/{filename}', [Instructor\ImageLibraryController::class, 'delete'])
        ->name('image-library.delete');
});
