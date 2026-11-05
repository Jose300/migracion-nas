<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
    /**
     * Test that the login page is accessible.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Ingresa a tu cuenta');
    }

    /**
     * Test that login screen displays remembered email and checks the remember box.
     */
    public function test_login_screen_displays_remembered_email(): void
    {
        $response = $this->withCookie('remember_email', 'jose.perera74@gmail.com')
            ->get('/');

        $response->assertStatus(200);
        $response->assertSee('value="jose.perera74@gmail.com"', false);
        $response->assertSee('checked', false);
    }

    /**
     * Test that guest is redirected from identity page.
     */
    public function test_guest_cannot_access_identity_page(): void
    {
        $response = $this->get('/configuracion/identidad');
        $response->assertRedirect('/');
    }

    /**
     * Test Setting model helper fallback without DB error.
     */
    public function test_setting_helper_returns_default(): void
    {
        $this->assertEquals('Default Value', setting('non_existing_key_xyz', 'Default Value'));
    }
}
