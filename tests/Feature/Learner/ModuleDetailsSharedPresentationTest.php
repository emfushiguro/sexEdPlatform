<?php

namespace Tests\Feature\Learner;

use App\Models\Lesson;
use App\Models\Module;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ModuleDetailsSharedPresentationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_learner_module_details_keeps_shared_overview_and_curriculum_visible(): void
    {
        $learner = $this->createMinorLearner(15);
        $module = Module::factory()->create([
            'is_published' => true,
            'current_review_status' => 'approved',
            'min_age' => 5,
            'max_age' => 100,
        ]);
        Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Learner Curriculum Lesson',
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee($module->title)
            ->assertSee('Learner Curriculum Lesson');
    }
}
