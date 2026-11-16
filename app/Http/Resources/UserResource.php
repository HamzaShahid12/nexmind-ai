<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'avatar_url' => $this->avatar_url,
            'status' => $this->status,
            'credits_balance' => $this->credits_balance,
            'credits_reset_at' => $this->credits_reset_at,
            'language' => $this->language,
            'tone' => $this->tone,
            'notify_email' => $this->notify_email,
            'deletion_requested_at' => $this->deletion_requested_at,
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? ['id' => $this->plan->id, 'name' => $this->plan->name, 'slug' => $this->plan->slug, 'monthly_credits' => $this->plan->monthly_credits] : null),
            'roles' => $this->getRoleNames()->values(),
            'created_at' => $this->created_at,
        ];
    }
}
