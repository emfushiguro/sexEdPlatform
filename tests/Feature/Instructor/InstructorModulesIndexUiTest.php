<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorModulesIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_modules_index_has_single_search_block_and_inline_status_metadata(): void
    {
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
}
