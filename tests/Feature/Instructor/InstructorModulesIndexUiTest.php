<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorModulesIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_modules_index_has_single_search_block_and_inline_status_metadata(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'UI Module',
            'is_published' => true,
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.modules.index'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'id="modules-local-search"'));
        $this->assertStringContainsString('module-meta-status-inline', $html);
    }

    public function test_modules_index_counts_legacy_lesson_quizzes_for_owned_module(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Legacy Quiz Count Module',
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        Quiz::factory()->create([
            'module_id' => null,
            'lesson_id' => $lesson->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.modules.index'));
        $response->assertOk();

        $paginator = $response->viewData('modules');
        $listedModule = $paginator->getCollection()->firstWhere('id', $module->id);

        $this->assertNotNull($listedModule);
        $this->assertSame(1, (int) $listedModule->quizzes_count);
    }
}
