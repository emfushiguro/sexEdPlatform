<?php

namespace Tests\Unit\Services;

use App\Support\ContentPanelContext;
use Tests\TestCase;

class ContentPanelContextTest extends TestCase
{
    public function test_resolves_admin_and_instructor_panel_from_route_name(): void
    {
        $this->assertSame('admin', ContentPanelContext::fromRouteName('admin.modules.index')->panel());
        $this->assertSame('instructor', ContentPanelContext::fromRouteName('instructor.modules.index')->panel());
    }

    public function test_generates_layout_and_route_names_for_each_panel(): void
    {
        $adminContext = ContentPanelContext::fromRouteName('admin.modules.index');
        $instructorContext = ContentPanelContext::fromRouteName('instructor.modules.index');

        $this->assertTrue($adminContext->isAdmin());
        $this->assertFalse($adminContext->isInstructor());
        $this->assertSame('layouts.admin', $adminContext->layout());
        $this->assertSame('admin.modules.show', $adminContext->name('modules.show'));

        $this->assertFalse($instructorContext->isAdmin());
        $this->assertTrue($instructorContext->isInstructor());
        $this->assertSame('layouts.instructor-app', $instructorContext->layout());
        $this->assertSame('instructor.modules.show', $instructorContext->name('modules.show'));
    }
}
