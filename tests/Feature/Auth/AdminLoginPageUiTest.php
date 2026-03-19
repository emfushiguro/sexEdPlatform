<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class AdminLoginPageUiTest extends TestCase
{
    public function test_admin_login_page_uses_redesigned_ui_copy_and_structure(): void
    {
        $this->withoutVite();

        $response = $this->get(route('admin.login'));

        $response->assertOk()
            ->assertSee('Administrator Command Center', false)
            ->assertSee('Secure access for platform operators', false)
            ->assertSee('Admin Email', false)
            ->assertSee('Admin Password', false)
            ->assertSee('Enter Secure Panel', false)
            ->assertSee(route('admin.login.submit'), false);
    }
}
