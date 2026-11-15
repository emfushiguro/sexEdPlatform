<?php

namespace Tests\Feature\Console;

use App\Models\LearningPath;
use App\Models\Module;
use Tests\TestCase;

class AuditLegacyKidsContentTest extends TestCase
{
    public function test_audit_reports_legacy_reasons_without_writing_or_exposing_content(): void
    {
        $kidsOnly = Module::factory()->create([
            'title' => 'Private Kids Module',
            'description' => 'Private description',
        ]);
        $kidsOnly->learnerCategories()->create(['category' => 'kids']);

        $mixed = LearningPath::factory()->create([
            'title' => 'Mixed path',
            'description' => 'Path description',
        ]);
        $mixed->learnerCategories()->createMany([
            ['category' => 'kids'],
            ['category' => 'teens'],
        ]);

        $before = [
            'module' => $kidsOnly->fresh()->getRawOriginal('updated_at'),
            'path' => $mixed->fresh()->getRawOriginal('updated_at'),
        ];

        $this->artisan('content:audit-legacy-kids')
            ->expectsOutputToContain('modules | '.$kidsOnly->id.' | kids_only | remaining=none')
            ->expectsOutputToContain('learning_paths | '.$mixed->id.' | mixed_with_kids | remaining=teens')
            ->expectsOutputToContain('modules | '.$kidsOnly->id.' | kids_wording | remaining=none')
            ->doesntExpectOutputToContain('Private description')
            ->assertSuccessful();

        $this->assertSame($before['module'], $kidsOnly->fresh()->getRawOriginal('updated_at'));
        $this->assertSame($before['path'], $mixed->fresh()->getRawOriginal('updated_at'));
    }
}
