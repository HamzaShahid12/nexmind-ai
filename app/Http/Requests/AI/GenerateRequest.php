<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for every generator tool, selected by the route's "tool" default.
 * Note: there is deliberately no credit/cost field. Costs are read from the database.
 */
class GenerateRequest extends FormRequest
{
    private const TONES = ['professional', 'friendly', 'formal', 'conversational', 'persuasive', 'creative'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tone = ['required', Rule::in(self::TONES)];
        $opt = fn (int $max) => ['nullable', 'string', 'max:'.$max];

        return match ($this->route('tool')) {
            'content-writer' => [
                'content_type' => ['required', Rule::in(['blog article', 'website content', 'product-related article', 'business description', 'introduction', 'professional bio', 'marketing copy'])],
                'topic' => ['required', 'string', 'max:500'],
                'keywords' => $opt(300), 'audience' => $opt(200), 'tone' => $tone,
                'language' => $opt(50),
                'length' => ['required', Rule::in(['short', 'medium', 'long'])],
                'instructions' => $opt(1000),
            ],
            'product-description' => [
                'product_name' => ['required', 'string', 'max:200'],
                'category' => $opt(100), 'features' => $opt(2000), 'specifications' => $opt(2000),
                'audience' => $opt(200), 'brand' => $opt(100), 'keywords' => $opt(300), 'tone' => $tone,
            ],
            'social-media' => [
                'platform' => ['required', Rule::in(['facebook', 'instagram', 'linkedin', 'x'])],
                'topic' => ['required', 'string', 'max:500'],
                'business_name' => $opt(150), 'audience' => $opt(200), 'tone' => $tone, 'keywords' => $opt(300),
                'variations' => ['required', 'integer', 'min:1', 'max:5'],
            ],
            'seo-assistant' => [
                'page_title' => ['required', 'string', 'max:200'],
                'content' => ['required', 'string', 'max:20000'],
                'main_keyword' => ['required', 'string', 'max:100'],
                'secondary_keywords' => $opt(300), 'audience' => $opt(200),
            ],
            'email-generator' => [
                'purpose' => ['required', 'string', 'max:300'],
                'recipient_type' => ['required', 'string', 'max:100'],
                'subject' => $opt(200),
                'key_information' => ['required', 'string', 'max:3000'],
                'tone' => $tone,
                'length' => ['required', Rule::in(['short', 'medium', 'long'])],
            ],
            default => abort(404),
        };
    }
}
