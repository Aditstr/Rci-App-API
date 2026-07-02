<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'case_id'          => $this->case_id,
            'uploaded_by'      => $this->uploaded_by,
            'file_name'        => $this->file_name,
            'file_path'        => $this->file_path,
            'file_type'        => $this->file_type,
            'file_size'        => $this->file_size,
            'document_type'    => $this->document_type,
            'ai_review_status' => $this->ai_review_status,
            'ai_review_result' => $this->ai_review_result,
            'created_at'       => $this->created_at,

            // Relations (only when loaded)
            'uploader' => new UserResource($this->whenLoaded('uploader')),
        ];
    }
}
