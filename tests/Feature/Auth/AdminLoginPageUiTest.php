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
            ->assertSee('Administrator Login', false)
            ->assertSee('Secure access for platform operators', false)
            ->assertSee('Email', false)
            ->assertSee('Password', false)
            ->assertSee('Enter Secure Panel', false)
            ->assertSee('#A30EB2', false)
            ->assertSee('#730DB1', false)
            ->assertSee('#3B0CB1', false)
            ->assertDontSee('#0F172A', false)
            ->assertSee(route('admin.login.submit'), false);
    }
}
