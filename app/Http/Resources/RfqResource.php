<?php

namespace App\Http\Resources;

use App\Models\Rfq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Rfq */
class RfqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $priority = Rfq::PRIORITIES[$this->priority_code] ?? null;

        return [
            'id' => $this->id,
            'enquiry_no' => $this->enquiry_no,
            'rfq_date' => $this->rfq_date?->format('Y-m-d'),
            'type' => $this->type,
            'source' => $this->source,
            'area' => $this->area,
            'opportunity_name' => $this->opportunity_name,
            'description' => $this->description,
            'customer_ref' => $this->customer_ref,
            'quote_no' => $this->quote_no,
            'customer' => $this->customer,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'supplier' => $this->supplier,
            'has_supplier_quote' => $this->has_supplier_quote,
            'has_brochure' => $this->has_brochure,
            'has_drawing' => $this->has_drawing,
            'status' => $this->status,
            'priority_code' => $this->priority_code,
            'priority_guide' => $priority['guide'] ?? null,
            'priority_pic' => $priority['pic'] ?? null,
            'po_received' => $this->po_received,
            'po_number' => $this->po_number,
            'current_pic' => $this->current_pic,
            'action_plan' => $this->action_plan,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'amount' => $this->amount,
            'latest_update' => $this->whenLoaded('latestUpdate', fn () => $this->latestUpdate ? [
                'update_date' => $this->latestUpdate->update_date->format('Y-m-d'),
                'note' => $this->latestUpdate->note,
            ] : null),
            'updates' => $this->whenLoaded('updates', fn () => $this->updates->map(fn ($u) => [
                'id' => $u->id,
                'update_date' => $u->update_date->format('Y-m-d'),
                'note' => $u->note,
                'priority_code' => $u->priority_code,
                'user' => $u->user?->name,
            ])),
        ];
    }
}
