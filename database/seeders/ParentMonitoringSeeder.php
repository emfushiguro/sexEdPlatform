<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Models\Achievement;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserGamification;
use App\Models\UserProgress;
use App\Notifications\Parent\ChildEnrollmentApprovalRequestedNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParentMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        $parent = $this->upsertParent();
        $child = $this->upsertChild();

        ParentChildAccount::query()->updateOrCreate(
            [
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
            ],
            [
                'can_view_progress' => true,
                'can_view_quiz_answers' => true,
                'can_approve_content' => true,
                'verification_status' => 'approved',
                'verification_document_path' => 'child-verifications/seeder/monitoring-seed.pdf',
                'verification_rejection_reason' => null,
                'verification_approved_at' => now()->subDays(20),
                'verification_reviewed_at' => now()->subDays(20),
                'relationship_verified_at' => now()->subDays(20),
            ]
        );

        $modules = Module::query()
            ->where('is_published', true)
            ->whereHas('lessons')
            ->whereHas('quizzes')
            ->with([
                'lessons:id,module_id,title',
                'quizzes.questions.options',
            ])
            ->orderBy('id')
            ->get();

        if ($modules->count() < 2) {
            if ($this->command) {
                $this->command->warn('ParentMonitoringSeeder skipped: at least 2 published modules with lessons and quizzes are required.');
            }

            return;
        }

        $approvedModule = $modules->first();
        $pendingModule = $modules->skip(1)->first();

        if (! $approvedModule || ! $pendingModule) {
            return;
        }

        ModuleEnrollment::query()->updateOrCreate(
            [
                'user_id' => $child->id,
                'module_id' => $approvedModule->id,
            ],
            [
                'status' => EnrollmentStatus::Approved->value,
                'enrolled_at' => now()->subDays(14),
                'completed_at' => null,
                'completion_percentage' => 55,
                'rejection_reason_code' => null,
                'rejection_reason_note' => null,
                'rejected_at' => null,
            ]
        );

        $pendingEnrollment = ModuleEnrollment::query()->updateOrCreate(
            [
                'user_id' => $child->id,
                'module_id' => $pendingModule->id,
            ],
            [
                'status' => EnrollmentStatus::PendingParentApproval->value,
                'enrolled_at' => null,
                'completed_at' => null,
                'completion_percentage' => 0,
                'rejection_reason_code' => null,
                'rejection_reason_note' => null,
                'rejected_at' => null,
            ]
        );

        $this->seedProgress($child, $approvedModule->id, $approvedModule->lessons);
        $this->seedQuizAttempts($child, $approvedModule->quizzes->first());
        $this->seedAchievements($child);

        UserGamification::query()->updateOrCreate(
            ['user_id' => $child->id],
            [
                'level' => 4,
                'score' => 640,
                'total_points' => 640,
                'streak_count' => 6,
                'longest_streak' => 8,
                'streak_savers' => 1,
                'last_act_at' => now()->subHours(8),
            ]
        );

        $pendingEnrollment->loadMissing('module');

        $hasPendingApprovalNotification = $parent->unreadNotifications()
            ->where('type', ChildEnrollmentApprovalRequestedNotification::class)
            ->where('data->type', 'child_enrollment_approval_requested')
            ->where('data->child_user_id', $child->id)
            ->where('data->module_id', $pendingEnrollment->module_id)
            ->exists();

        if (! $hasPendingApprovalNotification) {
            $parent->notify(new ChildEnrollmentApprovalRequestedNotification($pendingEnrollment, $child));
        }

        if ($this->command) {
            $this->command->info('ParentMonitoringSeeder seeded parent monitoring fixtures (progress, quiz attempts, achievements, and pending approval notification).');
        }
    }

    private function upsertParent(): User
    {
        $parent = User::query()->updateOrCreate(
            ['email' => 'parent.monitoring@sexed.platform'],
            [
                'name' => 'Monitoring Parent',
                'first_name' => 'Monitoring',
                'last_name' => 'Parent',
                'birthdate' => now()->subYears(36)->toDateString(),
                'password' => 'password',
                'role' => 'learner',
                'status' => User::STATUS_ACTIVE,
                'account_type' => User::ACCOUNT_TYPE_PARENT,
                'verified' => true,
                'email_verified_at' => now(),
                'is_parent_registration' => true,
                'parent_verification_status' => 'approved',
                'parent_verification_approved_at' => now()->subDays(30),
                'parent_verification_reviewed_at' => now()->subDays(30),
            ]
        );

        if (! $parent->hasRole('learner')) {
            $parent->assignRole('learner');
        }

        $parent->learnerProfile()->updateOrCreate(
            ['user_id' => $parent->id],
            [
                'username' => 'monitoring-parent-' . $parent->id,
                'birthdate' => $parent->birthdate,
                'gender' => 'female',
                'city_code' => '402101000',
                'barangay_code' => '402101001',
                'barangay' => 'Sample Barangay',
                'province_code' => '402100000',
                'is_parent_account' => true,
                'requires_parental_consent' => false,
            ]
        );

        return $parent;
    }

    private function upsertChild(): User
    {
        $child = User::query()->updateOrCreate(
            ['email' => 'learner.monitoring@sexed.platform'],
            [
                'name' => 'Monitoring Learner',
                'first_name' => 'Monitoring',
                'last_name' => 'Learner',
                'birthdate' => now()->subYears(13)->toDateString(),
                'password' => 'password',
                'role' => 'learner',
                'status' => User::STATUS_ACTIVE,
                'account_type' => User::ACCOUNT_TYPE_LEARNER_TEEN,
                'verified' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $child->hasRole('learner')) {
            $child->assignRole('learner');
        }

        $child->learnerProfile()->updateOrCreate(
            ['user_id' => $child->id],
            [
                'username' => 'monitoring-learner-' . $child->id,
                'birthdate' => $child->birthdate,
                'gender' => 'male',
                'city_code' => '402101000',
                'barangay_code' => '402101001',
                'barangay' => 'Sample Barangay',
                'province_code' => '402100000',
                'is_parent_account' => false,
                'requires_parental_consent' => true,
            ]
        );

        return $child;
    }

    private function seedProgress(User $child, int $moduleId, \Illuminate\Support\Collection $lessons): void
    {
        $completedLessonCount = min(1, $lessons->count());

        foreach ($lessons->take(2)->values() as $index => $lesson) {
            $isCompleted = $index === 0;

            UserProgress::query()->updateOrCreate(
                [
                    'user_id' => $child->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'module_id' => $moduleId,
                    'completed' => $isCompleted,
                    'progress_percentage' => $isCompleted ? 100 : 45,
                    'completed_lessons_count' => $completedLessonCount,
                    'last_accessed_at' => now()->subDays(2 - $index),
                    'completed_at' => $isCompleted ? now()->subDays(3) : null,
                ]
            );
        }
    }

    private function seedQuizAttempts(User $child, ?Quiz $quiz): void
    {
        if (! $quiz || $quiz->questions->isEmpty()) {
            return;
        }

        $passingAttempt = $this->buildAttemptPayload($quiz, true);
        $failingAttempt = $this->buildAttemptPayload($quiz, false);

        $passingStartedAt = Carbon::create(2026, 4, 1, 9, 0, 0);
        $failingStartedAt = Carbon::create(2026, 4, 3, 9, 0, 0);

        QuizAttempt::query()->updateOrCreate(
            [
                'user_id' => $child->id,
                'quiz_id' => $quiz->id,
                'started_at' => $passingStartedAt,
            ],
            [
                'answers' => $passingAttempt['answers'],
                'score' => $passingAttempt['score'],
                'passed' => $passingAttempt['passed'],
                'completed_at' => (clone $passingStartedAt)->addMinutes(8),
            ]
        );

        QuizAttempt::query()->updateOrCreate(
            [
                'user_id' => $child->id,
                'quiz_id' => $quiz->id,
                'started_at' => $failingStartedAt,
            ],
            [
                'answers' => $failingAttempt['answers'],
                'score' => $failingAttempt['score'],
                'passed' => $failingAttempt['passed'],
                'completed_at' => (clone $failingStartedAt)->addMinutes(7),
            ]
        );
    }

    /**
     * @return array{answers: array<string, array<string, mixed>>, score: int, passed: bool}
     */
    private function buildAttemptPayload(Quiz $quiz, bool $shouldPass): array
    {
        $answers = [];
        $correctCount = 0;
        $totalQuestions = max(1, $quiz->questions->count());

        foreach ($quiz->questions as $question) {
            $correctOption = $question->options->firstWhere('is_correct', true);
            $firstOption = $question->options->first();
            $wrongOption = $question->options->firstWhere('is_correct', false);

            $selectedOption = $shouldPass
                ? ($correctOption ?? $firstOption)
                : ($wrongOption ?? $firstOption ?? $correctOption);

            $selectedValue = $selectedOption?->id;
            $correctValue = $correctOption?->id;
            $isCorrect = $correctValue !== null && $selectedValue === $correctValue;

            if ($isCorrect) {
                $correctCount++;
            }

            $answers[(string) $question->id] = [
                'selected' => $selectedValue,
                'correct' => $correctValue,
                'is_correct' => $isCorrect,
            ];
        }

        $score = (int) round(($correctCount / $totalQuestions) * 100);

        return [
            'answers' => $answers,
            'score' => $score,
            'passed' => $score >= (int) ($quiz->passing_score ?? 70),
        ];
    }

    private function seedAchievements(User $child): void
    {
        $achievements = Achievement::query()->orderBy('id')->take(2)->get();

        if ($achievements->isEmpty()) {
            $achievements = collect([
                Achievement::query()->create([
                    'title' => 'Monitoring Seed Achievement',
                    'description' => 'Generated by ParentMonitoringSeeder for dashboard validation.',
                    'icon' => '🏅',
                    'requirement' => 'Complete seeded parent monitoring fixtures',
                ]),
            ]);
        }

        foreach ($achievements as $index => $achievement) {
            DB::table('rewards_logs')->updateOrInsert(
                [
                    'user_id' => $child->id,
                    'achievement_id' => $achievement->id,
                ],
                [
                    'type' => 'achievement',
                    'reason' => 'Seeded parent monitoring fixture',
                    'earned_at' => now()->subDays(4 + $index),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
