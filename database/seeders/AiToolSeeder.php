<?php

namespace Database\Seeders;

use App\Models\AiTool;
use Illuminate\Database\Seeder;

class AiToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            ['slug' => 'ai-chat', 'name' => 'AI Chat Assistant', 'category' => 'Productivity', 'credit_cost' => 1, 'max_output_tokens' => 1200, 'description' => 'Converse with an AI assistant and continue past conversations.'],
            ['slug' => 'content-writer', 'name' => 'AI Content Writer', 'category' => 'Writing', 'credit_cost' => 5, 'max_output_tokens' => 2200, 'description' => 'Blog articles, website copy, bios and marketing text.'],
            ['slug' => 'product-description', 'name' => 'Product Description Generator', 'category' => 'Ecommerce', 'credit_cost' => 3, 'max_output_tokens' => 1500, 'description' => 'Titles, descriptions, features, meta tags and product tags.'],
            ['slug' => 'social-media', 'name' => 'Social Media Generator', 'category' => 'Marketing', 'credit_cost' => 2, 'max_output_tokens' => 1500, 'description' => 'Captions, hashtags and calls to action for major platforms.'],
            ['slug' => 'seo-assistant', 'name' => 'SEO Assistant', 'category' => 'SEO', 'credit_cost' => 3, 'max_output_tokens' => 1500, 'description' => 'SEO suggestions (not guaranteed rankings).'],
            ['slug' => 'email-generator', 'name' => 'Email Generator', 'category' => 'Writing', 'credit_cost' => 2, 'max_output_tokens' => 1200, 'description' => 'Subject lines, bodies and follow-ups.'],
            ['slug' => 'document-summarizer', 'name' => 'Document Summarizer', 'category' => 'Productivity', 'credit_cost' => 5, 'max_output_tokens' => 1500, 'description' => 'Summaries, key points and Q&A for PDF, TXT and DOCX files.'],
        ];

        foreach ($tools as $i => $tool) {
            // Do not overwrite admin edits (cost, active flag...) when re-seeding.
            AiTool::firstOrCreate(['slug' => $tool['slug']], $tool + ['sort_order' => $i + 1, 'is_active' => true, 'temperature' => 0.7]);
        }
    }
}
