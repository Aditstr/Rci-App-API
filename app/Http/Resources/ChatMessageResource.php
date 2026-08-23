<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'case_id'     => $this->case_id,
            'sender_id'   => $this->sender_id,
            'message'     => $this->message,
            'attachments' => $this->attachments,
            'is_read'     => $this->is_read,
            'read_at'     => $this->read_at,
            'created_at'  => $this->created_at,
            'reported_by_me' => $this->whenLoaded(
                'complianceFlags',
                fn (): bool => $this->complianceFlags->isNotEmpty(),
            ),
            'can_report' => (bool) (
                $request->user()?->isClient()
                && $this->relationLoaded('sender')
                && $this->sender?->isExpert()
                && $request->user()->id !== $this->sender_id
            ),

            // Relations (only when loaded)
            'sender' => new UserResource($this->whenLoaded('sender')),
        ];
    }
}
