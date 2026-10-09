<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Trainingresource extends JsonResource
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
            'id_training' => $this->id_training,
            'division_training' => $this->division_training,
            'name_training' => $this->name_training,
            'by' => $this->by,
            // 'date' => Carbon::parse($this->date)->translatedFormat('d F Y'),
            // 'date_raw' => Carbon::parse($this->date)->format('Y-m-d'),
            'participants_count' => $this->whenCounted('participants'),
        ];
    }
}
