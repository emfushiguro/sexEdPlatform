<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Certificate;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateIssuedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_certificate_generation_notifies_learner_and_module_instructor(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'final_quiz_id' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        UserProgress::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $this->actingAs($learner)
            ->post(route('learner.certificates.check', $module))
            ->assertRedirect();

        $certificate = Certificate::query()
            ->where('user_id', $learner->id)
            ->where('module_id', $module->id)
            ->first();

        $this->assertNotNull($certificate);

        $learnerNotification = $learner->fresh()->notifications()->latest()->first();
        $instructorNotification = $instructor->fresh()->notifications()->latest()->first();

        $this->assertNotNull($learnerNotification);
        $this->assertNotNull($instructorNotification);

        $this->assertSame('certificate_issued', data_get($learnerNotification->data, 'type'));
        $this->assertSame('learner_certificate_issued', data_get($instructorNotification->data, 'type'));
        $this->assertSame($learner->id, (int) data_get($instructorNotification->data, 'learner_id'));
    }
}
