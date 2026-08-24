<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'qty' => $this->qty,
            'transactions_id' => new TransactionResource($this->whenLoaded('transaction')),
            'items_id' => new ItemsResourcec($this->whenLoaded('item')),
        ];
    }
}
