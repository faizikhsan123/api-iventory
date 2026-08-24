<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'qty' => $this->qty,
            'type' => $this->type,
            'note' => $this->note,
            'user_id' => new UserResource($this->whenLoaded('user')),
            'item_id' => new ItemsResourcec($this->whenLoaded('item')),
            'supplier_id' => new SupplierResource($this->whenLoaded('supplier')),
        ];
    }
}
