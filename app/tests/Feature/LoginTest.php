<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_log_in_and_reach_the_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'super-secret',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'super-secret',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'super-secret',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_docs_page_requires_authentication(): void
    {
        $this->get('/docs')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_the_docs_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/docs')->assertOk()->assertSee('POST /api/otp/send', false);
    }
}
