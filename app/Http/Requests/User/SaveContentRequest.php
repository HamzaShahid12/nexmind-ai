<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SaveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ai_generation_id' => ['nullable', 'integer'],
            'title' => ['required_without:ai_generation_id', 'nullable', 'string', 'max:200'],
            'content' => ['required_without:ai_generation_id', 'nullable', 'string', 'max:100000'],
            'category' => ['nullable', 'string', 'max:60'],
        ];
    }
}
