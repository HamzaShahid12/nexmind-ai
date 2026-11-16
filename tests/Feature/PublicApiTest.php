<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Database\Seeders\BlogSeeder;
use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparesApp;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use PreparesApp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        $this->seed([FaqSeeder::class, BlogSeeder::class]);
    }

    public function test_plans_endpoint_lists_configured_plans_with_credits(): void
    {
        $this->getJson('/api/v1/plans')->assertOk()->assertJsonPath('data.0.slug', 'free')->assertJsonPath('data.0.monthly_credits', 100);
    }

    public function test_blog_lists_only_published_posts_and_hides_drafts(): void
    {
        BlogPost::create(['title' => 'Draft', 'slug' => 'draft', 'content' => 'x', 'status' => 'draft']);

        $slugs = collect($this->getJson('/api/v1/blog/posts')->assertOk()->json('data'))->pluck('slug');

        $this->assertTrue($slugs->contains('getting-started-with-ai-writing-tools'));
        $this->assertFalse($slugs->contains('draft'));
        $this->getJson('/api/v1/blog/posts/draft')->assertNotFound();
        $this->getJson('/api/v1/blog/posts/getting-started-with-ai-writing-tools')->assertOk()->assertJsonPath('data.title', 'Getting started with AI writing tools');
    }

    public function test_blog_search_and_category_filter(): void
    {
        $this->getJson('/api/v1/blog/posts?search=product')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/blog/posts?category=guides')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_faqs_are_returned_when_published(): void
    {
        $this->getJson('/api/v1/faqs')->assertOk()->assertJsonStructure(['data' => [['id', 'category', 'question', 'answer']]]);
    }

    public function test_contact_form_stores_a_message_and_validates(): void
    {
        $this->postJson('/api/v1/contact', ['name' => 'Sam', 'email' => 'sam@example.com', 'subject' => 'Hi', 'message' => 'I have a question about plans.'])->assertCreated();
        $this->assertDatabaseHas('contact_messages', ['email' => 'sam@example.com', 'is_read' => false]);

        $this->postJson('/api/v1/contact', ['name' => '', 'email' => 'nope', 'subject' => '', 'message' => 'short'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_admin_can_manage_contact_messages_and_regular_user_cannot(): void
    {
        $this->postJson('/api/v1/contact', ['name' => 'Sam', 'email' => 'sam@example.com', 'subject' => 'Hi', 'message' => 'I have a question about plans.']);
        $id = \App\Models\ContactMessage::first()->id;

        $this->actingAs($this->makeUser())->getJson('/api/v1/admin/contact-messages')->assertForbidden();

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->getJson("/api/v1/admin/contact-messages/$id")->assertOk();
        $this->assertDatabaseHas('contact_messages', ['id' => $id, 'is_read' => true]);
        $this->actingAs($admin)->deleteJson("/api/v1/admin/contact-messages/$id")->assertOk();
    }

    public function test_admin_can_publish_and_unpublish_a_post(): void
    {
        $admin = $this->makeUser('admin');

        $id = $this->actingAs($admin)->postJson('/api/v1/admin/blogs', ['title' => 'New Post', 'content' => 'Body', 'status' => 'draft'])->assertCreated()->json('data.id');
        $this->getJson('/api/v1/blog/posts/new-post')->assertNotFound();

        $this->actingAs($admin)->postJson("/api/v1/admin/blogs/$id/publish")->assertOk();
        $this->getJson('/api/v1/blog/posts/new-post')->assertOk();

        $this->actingAs($admin)->postJson("/api/v1/admin/blogs/$id/unpublish")->assertOk();
        $this->getJson('/api/v1/blog/posts/new-post')->assertNotFound();
    }

    public function test_sitemap_lists_public_pages_and_published_posts_only(): void
    {
        BlogPost::create(['title' => 'Hidden draft', 'slug' => 'hidden-draft', 'content' => 'x', 'status' => 'draft']);

        $response = $this->get('/sitemap.xml')->assertOk();
        $xml = $response->getContent();

        $this->assertStringContainsString('/pricing</loc>', $xml);
        $this->assertStringContainsString('/blog/getting-started-with-ai-writing-tools</loc>', $xml);
        $this->assertStringNotContainsString('hidden-draft', $xml);
        $this->assertStringNotContainsString('/dashboard', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
    }

    public function test_unknown_api_route_returns_clean_json_404(): void
    {
        $this->getJson('/api/v1/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }
}
