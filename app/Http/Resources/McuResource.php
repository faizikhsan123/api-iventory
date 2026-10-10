<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class McuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employes_id' => $this->employes_id,
            'employee_name' => $this->employes?->user?->name,
            'id_number' => $this->employes?->id_number,
            'division' => $this->employes?->division,
            'position' => $this->employes?->position,
            'place_name' => $this->place_name,
            'mcu_name' => $this->mcu_name,
            'mcu_date' => $this->mcu_date?->format('Y-m-d'),
            'document' => $this->document,
            'document_2' => $this->document_2,
            'summary' => $this->summary,
            'allergies' => $this->allergies,
            'next_mcu_date' => $this->next_mcu_date?->format('Y-m-d'),
            // diisi Mcu::flagLatest() oleh controller; default true kalau tidak diisi
            'is_latest' => (bool) ($this->resource->getAttribute('is_latest') ?? true),
            'created_at' => $this->created_at,
        ];
    }
}
