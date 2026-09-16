<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployesResource extends JsonResource
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
            'division' => $this->division,
            'position' => $this->position,
            'status' => $this->status,
            'user' => $this->whenLoaded('user'),
            'given_items_count' => $this->given_items_count ?? 0,

            // ambil dari transactionItems yang udah di-eager load, mapping tiap barisnya jadi format sendiri
            'items' => $this->whenLoaded('transactionItems', function () {
                return $this->transactionItems->map(function ($ti) {
                    return [
                        'item_name' => $ti->item->name ?? null,      // dari relasi .item
                        'qty' => $ti->qty,
                        'date' => $ti->transaction->date ?? null,     // dari relasi .transaction
                        'note' => $ti->transaction->note ?? null,
                    ];
                })->values();
            }),
        ];
    }
}
