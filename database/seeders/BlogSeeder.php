<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /** Two short sample articles so the blog is not empty. Replace or delete them from the admin panel. */
    public function run(): void
    {
        $guides = BlogCategory::firstOrCreate(['slug' => 'guides'], ['name' => 'Guides']);
        $ecommerce = BlogCategory::firstOrCreate(['slug' => 'ecommerce'], ['name' => 'Ecommerce']);

        $posts = [
            [
                'slug' => 'getting-started-with-ai-writing-tools', 'blog_category_id' => $guides->id,
                'title' => 'Getting started with AI writing tools',
                'excerpt' => 'A short, practical approach to getting useful drafts from an AI writing assistant.',
                'content' => "AI writing tools are best treated as a fast first-draft partner, not a replacement for your judgment.\n\nStart with a clear goal: who is the reader, and what should they do after reading? The more specific the details you provide, such as audience, tone and key facts, the more useful the first draft will be.\n\nAlways review the result. Check facts, names and numbers yourself, and edit the text so it sounds like your brand. Treat the output as a starting point.",
                'meta_title' => 'Getting started with AI writing tools', 'meta_description' => 'A practical introduction to getting useful first drafts from an AI writing assistant.',
            ],
            [
                'slug' => 'writing-product-descriptions-that-sell', 'blog_category_id' => $ecommerce->id,
                'title' => 'Writing product descriptions that help customers decide',
                'excerpt' => 'What to include in a product description, and how AI can speed up the first draft.',
                'content' => "A good product description answers the questions a shopper cannot ask in person: what is it made of, who is it for, and how will it help?\n\nList the real features first, then explain the benefit of each in plain language. Avoid claims you cannot support.\n\nAn AI tool can turn your feature list into a title, a short description and meta tags in seconds. Give it accurate specifications and review the result before publishing.",
                'meta_title' => 'Writing product descriptions that help customers decide', 'meta_description' => 'What to include in a product description and how AI can speed up the first draft.',
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::firstOrCreate(['slug' => $post['slug']], $post + ['status' => 'published', 'published_at' => now()]);
        }
    }
}
