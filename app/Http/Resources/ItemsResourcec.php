<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemsResourcec extends JsonResource
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
            'part_number' => $this->part_number,
            'file' => $this->file,
            'name' => $this->name,
            'category' => $this->category,
            'brand' => $this->brand,
            'type' => $this->type,
            'size' => $this->size,
            'unit' => $this->unit,
            'min_stock' => $this->min_stock,
            'current_stock' => $this->current_stock,
            'status' => $this->status,
            'description' => $this->description,
        ];
    }
}
