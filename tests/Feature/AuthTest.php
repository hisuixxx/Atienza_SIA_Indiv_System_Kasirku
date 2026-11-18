<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('auth/login'));
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('auth/register'));
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Recover Password');
        $response->assertSee('Back to Home');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_staff_redirects_to_staff_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_redirects_to_menu_after_login(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('menu'));
    }

    public function test_new_users_can_register_as_customer(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Coffee Lover',
            'email' => 'lover@example.com',
            'phone' => '09123456789',
            'address' => '123 Coffee Street',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'lover@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::ROLE_CUSTOMER, $user->role);
        $response->assertRedirect(route('menu'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_deactivated_users_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_role_middleware_blocks_customer_from_admin_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_role_middleware_allows_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }
}
