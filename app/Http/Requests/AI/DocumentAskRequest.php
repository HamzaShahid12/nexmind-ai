<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class DocumentAskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['question' => ['required', 'string', 'max:1000']];
    }
}
