<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `mimes:` guesses DOCX as "zip" on many systems, so extension and sniffed MIME are checked separately.
            // DocumentService then re-checks the pair and extraction fails on content that is not really the claimed type.
            'file' => [
                'required', 'file',
                'max:'.config('ai.documents.max_kb'),
                'extensions:pdf,txt,docx',
                'mimetypes:application/pdf,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream',
            ],
        ];
    }

    public function messages(): array
    {
        $message = 'Only PDF, TXT and DOCX files are allowed.';

        return [
            'file.extensions' => $message,
            'file.mimetypes' => $message,
            'file.max' => 'The file is too large. The maximum size is '.round(config('ai.documents.max_kb') / 1024, 1).' MB.',
        ];
    }
}
