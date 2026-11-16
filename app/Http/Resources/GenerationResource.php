<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AiGeneration  (list view: preview only) */
class GenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'tool' => $this->whenLoaded('tool', fn () => ['id' => $this->tool->id, 'name' => $this->tool->name, 'slug' => $this->tool->slug, 'category' => $this->tool->category]),
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]),
            'credits_used' => $this->credits_used,
            'preview' => $this->preview(),
            'created_at' => $this->created_at,
        ];
    }
}
