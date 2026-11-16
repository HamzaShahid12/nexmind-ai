<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparesApp;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use PreparesApp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    public function test_user_can_register_and_receives_free_credits_and_role(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'ada@example.com')->assertJsonPath('data.credits_balance', 100);

        $user = User::where('email', 'ada@example.com')->first();
        $this->assertTrue($user->hasRole('user'));
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $user->id, 'type' => 'signup', 'amount' => 100]);
        $this->assertNotSame('Password123', $user->password);          // hashed
    }

    public function test_registration_cannot_set_privileged_fields(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Eve', 'email' => 'eve@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123',
            'credits_balance' => 99999, 'status' => 'active', 'role' => 'super_admin',
        ])->assertCreated();

        $user = User::where('email', 'eve@example.com')->first();
        $this->assertSame(100, $user->credits_balance);
        $this->assertFalse($user->hasRole('super_admin'));
    }

    public function test_registration_validates_input(): void
    {
        $this->postJson('/api/v1/register', ['name' => '', 'email' => 'bad', 'password' => 'short'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login_and_fetch_profile(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('data.email', $user->email);

        $this->getJson('/api/v1/user')->assertOk()->assertJsonPath('data.roles.0', 'user');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['status' => 'suspended'])->save();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->assertForbidden();
    }

    public function test_suspended_user_is_blocked_from_authenticated_routes(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($user)->getJson('/api/v1/dashboard')->assertForbidden();
    }

    public function test_protected_routes_require_authentication(): void
    {
        foreach (['/api/v1/user', '/api/v1/dashboard', '/api/v1/history', '/api/v1/credits', '/api/v1/saved-content', '/api/v1/profile'] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    public function test_user_can_logout(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->postJson('/api/v1/logout')->assertOk();
        $this->assertGuest('web');
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->putJson('/api/v1/profile/password', [
            'current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->actingAs($user)->putJson('/api/v1/profile/password', [
            'current_password' => 'password', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertOk();
    }

    public function test_profile_update_ignores_privileged_fields(): void
    {
        $user = $this->makeUser('user', 10);

        $this->actingAs($user)->putJson('/api/v1/profile', ['name' => 'New Name', 'credits_balance' => 5000, 'status' => 'suspended'])->assertOk();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame(10, $user->credits_balance);
        $this->assertSame('active', $user->status);
    }
}
