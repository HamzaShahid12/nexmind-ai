<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparesApp;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use PreparesApp, RefreshDatabase;

    private const ADMIN_GETS = [
        '/api/v1/admin/dashboard', '/api/v1/admin/users', '/api/v1/admin/roles', '/api/v1/admin/ai-tools', '/api/v1/admin/ai-settings',
        '/api/v1/admin/plans', '/api/v1/admin/credits', '/api/v1/admin/generations', '/api/v1/admin/usage', '/api/v1/admin/transactions',
        '/api/v1/admin/blogs', '/api/v1/admin/categories', '/api/v1/admin/faqs', '/api/v1/admin/contact-messages', '/api/v1/admin/settings',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    public function test_guests_get_401_on_every_admin_route(): void
    {
        foreach (self::ADMIN_GETS as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    public function test_regular_users_get_403_on_every_admin_route(): void
    {
        $user = $this->makeUser();

        foreach (self::ADMIN_GETS as $url) {
            $this->actingAs($user)->getJson($url)->assertForbidden();
        }
        $this->actingAs($user)->postJson("/api/v1/admin/users/{$user->id}/credits", ['amount' => 1000, 'reason' => 'self-grant'])->assertForbidden();
        $this->assertSame(50, $user->fresh()->credits_balance);
    }

    public function test_admins_can_open_admin_routes(): void
    {
        $admin = $this->makeUser('admin');

        foreach (self::ADMIN_GETS as $url) {
            $this->actingAs($admin)->getJson($url)->assertOk();
        }
    }

    public function test_admin_dashboard_reports_real_counts(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeUser();

        $this->actingAs($admin)->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonPath('data.total_users', 2)->assertJsonPath('data.total_generations', 0);
    }

    public function test_admin_can_suspend_and_activate_a_user(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        $this->actingAs($admin)->postJson("/api/v1/admin/users/{$user->id}/suspend")->assertOk()->assertJsonPath('data.status', 'suspended');
        $this->actingAs($admin)->postJson("/api/v1/admin/users/{$user->id}/activate")->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin.user.suspended', 'user_id' => $admin->id]);
    }

    public function test_last_active_super_admin_cannot_be_suspended_deleted_or_demoted(): void
    {
        $super = $this->makeUser('super_admin');
        $other = $this->makeUser('super_admin');
        $other->forceFill(['status' => 'suspended'])->save();            // not "active", so $super is the last active one

        $this->actingAs($super)->postJson("/api/v1/admin/users/{$other->id}/activate")->assertOk();   // now two active
        $this->actingAs($super)->postJson("/api/v1/admin/users/{$super->id}/suspend")->assertStatus(422);   // cannot suspend self
        $this->actingAs($super)->deleteJson("/api/v1/admin/users/{$super->id}")->assertStatus(422);

        // Suspend the other one -> $super becomes the last active super admin and cannot demote themselves.
        $this->actingAs($super)->postJson("/api/v1/admin/users/{$other->id}/suspend")->assertOk();
        $this->actingAs($super)->putJson("/api/v1/admin/users/{$super->id}/roles", ['roles' => ['admin']])->assertStatus(422);
        $this->assertTrue($super->fresh()->hasRole('super_admin'));

        // With a second active super admin the demotion is allowed.
        $this->actingAs($super)->postJson("/api/v1/admin/users/{$other->id}/activate")->assertOk();
        $this->actingAs($super)->putJson("/api/v1/admin/users/{$super->id}/roles", ['roles' => ['admin']])->assertOk();
        $this->assertFalse($super->fresh()->hasRole('super_admin'));
    }

    public function test_regular_admin_cannot_touch_super_admins_or_change_roles(): void
    {
        $admin = $this->makeUser('admin');
        $super = $this->makeUser('super_admin');
        $user = $this->makeUser();

        $this->actingAs($admin)->postJson("/api/v1/admin/users/{$super->id}/suspend")->assertForbidden();
        $this->actingAs($admin)->deleteJson("/api/v1/admin/users/{$super->id}")->assertForbidden();
        $this->actingAs($admin)->putJson("/api/v1/admin/users/{$user->id}/roles", ['roles' => ['admin']])->assertForbidden();
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function test_super_admin_can_assign_roles(): void
    {
        $super = $this->makeUser('super_admin');
        $user = $this->makeUser();

        $this->actingAs($super)->putJson("/api/v1/admin/users/{$user->id}/roles", ['roles' => ['admin']])->assertOk();
        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_admin_credit_adjustment_adds_and_deducts_with_audit_trail(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('user', 20);

        $this->actingAs($admin)->postJson("/api/v1/admin/users/{$user->id}/credits", ['amount' => 30, 'reason' => 'Goodwill'])->assertCreated();
        $this->assertSame(50, $user->fresh()->credits_balance);

        $this->actingAs($admin)->postJson("/api/v1/admin/users/{$user->id}/credits", ['amount' => -10, 'reason' => 'Correction'])->assertCreated();
        $this->assertSame(40, $user->fresh()->credits_balance);
        $this->assertDatabaseHas('credit_transactions', ['user_id' => $user->id, 'type' => 'admin_deduct', 'amount' => -10, 'admin_id' => $admin->id]);
    }

    public function test_admin_cannot_deduct_below_zero(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('user', 5);

        $this->actingAs($admin)->postJson("/api/v1/admin/users/{$user->id}/credits", ['amount' => -50, 'reason' => 'Too much'])->assertStatus(402);
        $this->assertSame(5, $user->fresh()->credits_balance);
    }

    public function test_ai_settings_never_expose_the_api_key(): void
    {
        config(['ai.providers.openai.key' => 'sk-super-secret-abcd']);
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/ai-settings')->assertOk()->assertJsonPath('data.api_key_configured', true);

        $this->assertStringNotContainsString('sk-super-secret', $response->getContent());
        $this->assertStringEndsWith('abcd', $response->json('data.api_key_hint'));
    }

    public function test_admin_can_edit_tool_cost_and_plan_allowance(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->putJson('/api/v1/admin/ai-tools/'.\App\Models\AiTool::where('slug', 'email-generator')->value('id'), ['credit_cost' => 4, 'is_active' => false])->assertOk();
        $this->assertDatabaseHas('ai_tools', ['slug' => 'email-generator', 'credit_cost' => 4, 'is_active' => false]);

        $free = \App\Models\Plan::where('slug', 'free')->first();
        $this->actingAs($admin)->putJson("/api/v1/admin/plans/{$free->id}", ['name' => 'Free', 'slug' => 'free', 'monthly_credits' => 150, 'is_default' => true, 'is_active' => true])->assertOk();
        $this->assertSame(150, $free->fresh()->monthly_credits);
    }

    public function test_default_plan_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $free = \App\Models\Plan::where('slug', 'free')->first();

        $this->actingAs($admin)->deleteJson("/api/v1/admin/plans/{$free->id}")->assertStatus(422);
    }

    public function test_deleted_users_are_soft_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        $this->actingAs($admin)->deleteJson("/api/v1/admin/users/{$user->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }
}
