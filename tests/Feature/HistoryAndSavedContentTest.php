<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use App\Models\AiTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparesApp;
use Tests\TestCase;

class HistoryAndSavedContentTest extends TestCase
{
    use PreparesApp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
    }

    private function generationFor(User $user, string $title = 'My email'): AiGeneration
    {
        return AiGeneration::create([
            'user_id' => $user->id, 'ai_tool_id' => AiTool::where('slug', 'email-generator')->value('id'),
            'title' => $title, 'input' => ['purpose' => 'x'], 'output' => json_encode($this->emailResult()), 'output_format' => 'json', 'credits_used' => 2,
        ]);
    }

    public function test_history_lists_only_the_owners_generations(): void
    {
        $alice = $this->makeUser();
        $bob = $this->makeUser();
        $this->generationFor($alice, 'Alice item');
        $this->generationFor($bob, 'Bob item');

        $response = $this->actingAs($alice)->getJson('/api/v1/history')->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Alice item'));
        $this->assertFalse($titles->contains('Bob item'));
    }

    public function test_history_supports_search_and_tool_filter(): void
    {
        $user = $this->makeUser();
        $this->generationFor($user, 'Quarterly report email');
        $this->generationFor($user, 'Welcome note');

        $this->actingAs($user)->getJson('/api/v1/history?search=Quarterly')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user)->getJson('/api/v1/history?tool=seo-assistant')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($user)->getJson('/api/v1/history?tool=email-generator')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_and_delete_own_generation(): void
    {
        $user = $this->makeUser();
        $generation = $this->generationFor($user);

        $this->actingAs($user)->getJson("/api/v1/history/{$generation->id}")->assertOk()->assertJsonPath('data.output.subject', 'Hello');
        $this->actingAs($user)->deleteJson("/api/v1/history/{$generation->id}")->assertOk();
        $this->assertDatabaseMissing('ai_generations', ['id' => $generation->id]);
    }

    public function test_user_cannot_view_or_delete_another_users_generation(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $generation = $this->generationFor($owner);

        $this->actingAs($intruder)->getJson("/api/v1/history/{$generation->id}")->assertForbidden();
        $this->actingAs($intruder)->deleteJson("/api/v1/history/{$generation->id}")->assertForbidden();
        $this->assertDatabaseHas('ai_generations', ['id' => $generation->id]);
    }

    public function test_user_can_save_their_generation_and_content_comes_from_the_server(): void
    {
        $user = $this->makeUser();
        $generation = $this->generationFor($user);

        $this->actingAs($user)->postJson('/api/v1/saved-content', ['ai_generation_id' => $generation->id, 'content' => 'forged by client'])
            ->assertCreated();

        $saved = $user->savedContents()->first();
        $this->assertStringContainsString('Body text', $saved->content);
        $this->assertStringNotContainsString('forged by client', $saved->content);
        $this->assertSame('Writing', $saved->category);
    }

    public function test_user_can_save_custom_content_and_search_it(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->postJson('/api/v1/saved-content', ['title' => 'Slogan', 'content' => 'Work smarter'])->assertCreated();
        $this->actingAs($user)->getJson('/api/v1/saved-content?search=smarter')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user)->getJson('/api/v1/saved-content?search=zzz')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_saving_requires_title_and_content_without_a_generation(): void
    {
        $this->actingAs($this->makeUser())->postJson('/api/v1/saved-content', [])->assertStatus(422)->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_user_cannot_save_another_users_generation(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $generation = $this->generationFor($owner);

        $this->actingAs($intruder)->postJson('/api/v1/saved-content', ['ai_generation_id' => $generation->id])->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_saved_content(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $saved = $owner->savedContents()->create(['title' => 'Mine', 'content' => 'secret']);

        $this->actingAs($intruder)->deleteJson("/api/v1/saved-content/{$saved->id}")->assertForbidden();
        $this->actingAs($owner)->deleteJson("/api/v1/saved-content/{$saved->id}")->assertOk();
    }

    public function test_dashboard_numbers_come_from_the_database(): void
    {
        $user = $this->makeUser('user', 37);
        $this->generationFor($user);
        $this->generationFor($user);

        $this->actingAs($user)->getJson('/api/v1/dashboard')->assertOk()
            ->assertJsonPath('data.credits_balance', 37)
            ->assertJsonPath('data.total_generations', 2)
            ->assertJsonPath('data.plan.name', 'Free');
    }
}
