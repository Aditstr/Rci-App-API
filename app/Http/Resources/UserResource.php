<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Only expose safe, public-facing fields.
     * Sensitive data like password, remember_token are excluded.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'role'        => $this->role,
            'phone'       => $this->phone,
            'avatar_url'  => $this->avatar_url,
            'is_verified' => $this->hasVerifiedEmail(),
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at,

            // Conditionally include relations
            'expert_profile' => new ExpertProfileResource($this->whenLoaded('expertProfile')),
            'wallet'         => new WalletResource($this->whenLoaded('wallet')),
        ];
    }
}
