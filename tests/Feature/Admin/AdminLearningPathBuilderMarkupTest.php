<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use PHPUnit\Framework\TestCase;

class AdminLearningPathBuilderMarkupTest extends TestCase
{
    public function test_learning_path_form_exposes_accessible_ordering_contract(): void
    {
        $markup = file_get_contents(dirname(__DIR__, 3).'\\resources\\views\\admin\\learning-paths\\form.blade.php');

        $this->assertIsString($markup);
        $this->assertStringContainsString('<ol', $markup);
        $this->assertStringContainsString('data-learning-path-index', $markup);
        $this->assertStringContainsString('aria-live="polite"', $markup);
        $this->assertStringContainsString('aria-describedby', $markup);
        $this->assertStringContainsString('aria-pressed', $markup);
        $this->assertStringContainsString('aria-posinset', $markup);
        $this->assertStringContainsString('module_ids[]', $markup);
        $this->assertStringContainsString('@pointerdown', $markup);
        $this->assertStringContainsString('@pointermove.window', $markup);
        $this->assertStringContainsString('@pointerup.window', $markup);
        $this->assertStringContainsString('@pointercancel.window', $markup);
        $this->assertStringContainsString('@keydown', $markup);
        $this->assertStringContainsString('mismatchedModuleIds', $markup);
    }
}
