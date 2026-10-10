<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'training_id' => $this->training_id,
            'employes_id' => $this->employes_id,
            'name' => $this->employes?->user?->name,
            'division' => $this->employes?->division,
            'position' => $this->employes?->position,
            'date' => $this->date?->translatedFormat('d F Y'),
            'date_raw' => $this->date?->format('Y-m-d'),
            'file' => $this->file,
            'notes' => $this->notes,
        ];
    }
}
