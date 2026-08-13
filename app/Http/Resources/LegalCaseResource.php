<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'case_number'         => $this->case_number,
            'title'               => $this->title,
            'description'         => $this->description,
            'category'            => $this->category,
            'status'              => $this->status,
            'priority'            => $this->priority,
            'is_marketplace'      => $this->is_marketplace,
            'client_id'           => $this->client_id,
            'expert_id'           => $this->expert_id,

            // AI analysis
            'ai_complexity_score' => $this->ai_complexity_score,
            'ai_estimated_cost'   => $this->ai_estimated_cost,
            'ai_review_result'    => $this->ai_review_result,

            // Quotation / Fee
            'proposed_fee'        => $this->proposed_fee,
            'fee_notes'           => $this->fee_notes,
            'fee_structure'       => $this->fee_structure,
            'quotation_status'    => $this->quotation_status,

            // Completion flow
            'completion_notes'    => $this->completion_notes,
            'expert_completed_at' => $this->expert_completed_at,
            'client_confirmed_at' => $this->client_confirmed_at,
            'dispute_reason'      => $this->when($this->status === 'dispute', $this->dispute_reason),
            'cancellation_reason' => $this->when($this->status === 'cancelled', $this->cancellation_reason),

            // Timestamps
            'submitted_at'        => $this->submitted_at,
            'assigned_at'         => $this->assigned_at,
            'completed_at'        => $this->completed_at,
            'created_at'          => $this->created_at,

            // Relations (only when loaded)
            'client'    => new UserResource($this->whenLoaded('client')),
            'expert'    => new UserResource($this->whenLoaded('expert')),
            'documents' => CaseDocumentResource::collection($this->whenLoaded('documents')),
        ];
    }
}
