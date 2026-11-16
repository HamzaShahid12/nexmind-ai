<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \App\Models\AiGeneration  (full output) */
class GenerationDetailResource extends GenerationResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'output' => $this->decodedOutput(),
            'output_format' => $this->output_format,
            'plain_text' => $this->plainText(),
            'input' => $this->input,
            'model' => $this->model,
        ];
    }
}
