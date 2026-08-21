<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Internal document paths (ktp_path, etc.) are excluded from public API.
     * They are only accessible via admin panel (Filament).
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'user_id'                => $this->user_id,
            'license_number'         => $this->license_number,
            'specialization_tags'    => $this->specialization_tags,
            'experience_years'       => $this->experience_years,
            'bio'                    => $this->bio,
            'is_verified'            => $this->is_verified,
            'rating'                 => $this->rating,
            'successful_cases_count' => $this->successful_cases_count,
            'current_workload'       => $this->current_workload,
            'verification_status'    => $this->verification_status,
            'verified_at'            => $this->verified_at,
            'created_at'             => $this->created_at,

            // Document paths are intentionally excluded from public API.
            // But frontend needs to know if they have uploaded them to hide the onboarding form.
            'has_documents'          => !empty($this->ktp_path) && !empty($this->ijazah_path),

            // Only include rejection_reason if status is rejected
            'rejection_reason' => $this->when(
                $this->verification_status === 'rejected',
                $this->rejection_reason
            ),
        ];
    }
}
