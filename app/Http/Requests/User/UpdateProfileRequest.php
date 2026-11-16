<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'language' => ['sometimes', 'string', 'max:50'],
            'tone' => ['sometimes', Rule::in(['professional', 'friendly', 'formal', 'conversational', 'persuasive', 'creative'])],
            'notify_email' => ['sometimes', 'boolean'],
            // SVG is intentionally excluded (XSS vector).
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }
}
