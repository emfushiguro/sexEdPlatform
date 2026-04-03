<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminContentReviewPreviewEndpointTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_can_fetch_topic_preview_payload(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->getJson(route('admin.content-reviews.preview', $reviewRequest) . '?node_type=topic&node_id=201')
            ->assertOk()
            ->assertJsonPath('node.type', 'topic')
            ->assertJsonPath('node.id', 201)
            ->assertJsonMissing(['text_content' => '<script>alert(1)</script><p>Safe</p>']);
    }

    public function test_non_admin_cannot_fetch_preview_payload(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($instructor)
            ->getJson(route('admin.content-reviews.preview', $reviewRequest) . '?node_type=topic&node_id=201')
            ->assertStatus(403);
    }

    private function createPendingReviewRequest(): ModuleReviewRequest
    {
        $instructor = $this->createUserWithRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'in_review',
        ]);

        $revision = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => [
                'module' => ['id' => $module->id, 'title' => $module->title],
                'lessons' => [
                    [
                        'attributes' => [
                            'id' => 101,
                            'title' => 'Lesson One',
                            'order' => 1,
                        ],
                        'topics' => [
                            [
                                'id' => 201,
                                'title' => 'Unsafe Topic',
                                'type' => 'text',
                                'text_content' => '<script>alert(1)</script><p>Safe</p>',
                                'order' => 1,
                            ],
                        ],
                    ],
                ],
                'quizzes' => [
                    [
                        'attributes' => [
                            'id' => 301,
                            'title' => 'Quiz A',
                        ],
                        'questions' => [],
                    ],
                ],
            ],
            'submitted_by' => $instructor->id,
            'status' => 'in_review',
            'submitted_at' => now(),
        ]);

        return ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $revision->id,
            'status' => 'in_review',
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
