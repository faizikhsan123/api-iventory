<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'activity' => $this->activity,
            'detail' => $this->detail,
            'user_id' => new UserResource($this->whenLoaded('user')),
            'date' => Carbon::parse($this->date)->translatedFormat('d F Y'),
            'type' => $this->type,
        ];
    }
}
