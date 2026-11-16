<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['General', 'What is NexMind AI?', 'NexMind AI is a platform of AI writing and productivity tools for businesses, ecommerce owners, marketers and creators.'],
            ['General', 'Do I need a credit card to sign up?', 'No. You can create a free account and use your monthly free credits without entering payment details.'],
            ['Credits & Plans', 'How do credits work?', 'Each AI tool costs a set number of credits per successful generation. Failed requests are not charged. Your balance is shown in your dashboard.'],
            ['Credits & Plans', 'Can I buy a paid plan?', 'Not yet. Pro and Business plans are planned, but online payments are not available at the moment.'],
            ['AI Tools', 'Does the SEO Assistant guarantee rankings?', 'No. It provides AI-generated suggestions. Search rankings depend on many factors outside any tool.'],
            ['AI Tools', 'Can NexMind AI post to my social accounts?', 'Not currently. It generates post copy that you can copy and publish yourself.'],
            ['Privacy & Security', 'What happens to documents I upload?', 'Files are validated, text is extracted on the server, and the uploaded file is deleted immediately. Extracted text is removed after the retention period.'],
        ];

        foreach ($faqs as $i => [$category, $q, $a]) {
            Faq::firstOrCreate(['question' => $q], ['category' => $category, 'answer' => $a, 'sort_order' => $i, 'is_published' => true]);
        }
    }
}
