<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer'],
        ];
    }
}
