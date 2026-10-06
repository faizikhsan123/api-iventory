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
            'id_number' => $this->id_number,
            'division' => $this->division,
            'file' => $this->file,
            'position' => $this->position,
            'status' => $this->status,
            'ktp_address' => $this->ktp_address,
            'actual_address' => $this->actual_address,
            'emergency_contact' => $this->emergency_contact,
            'user' => $this->whenLoaded('user'),
            'given_items_count' => $this->given_items_count ?? 0,
            'items' => $this->whenLoaded('transactionItems', function () {
                return $this->transactionItems
                    ->groupBy('transactions_id')   // FK ke transaction, sesuaikan nama kolomnya
                    ->map(function ($group) {
                        $transaction = $group->first()->transaction;

                        return [
                            'transaction_id' => $transaction->id ?? null,
                            'date' => $transaction->date ?? null,
                            'note' => $transaction->note ?? null,
                            'items' => $group->map(function ($ti) {
                                return [
                                    'item_name' => $ti->item->name ?? null,
                                    'qty' => $ti->qty,
                                ];
                            })->values(),
                        ];
                    })->values();
            }),
        ];
    }
}
