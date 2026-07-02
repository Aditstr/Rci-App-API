<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'wallet_id'      => $this->wallet_id,
            'amount'         => $this->amount,
            'type'           => $this->type,
            'status'         => $this->status,
            'description'    => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'created_at'     => $this->created_at,
        ];
    }
}
