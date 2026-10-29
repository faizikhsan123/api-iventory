<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        // ambil data karyawan
        $employeName = null;
        $division = null;
        $position = null;

        if ($this->employes) {
            $division = $this->employes->division;
            $position = $this->employes->position;

            if ($this->employes->user) {
                $employeName = $this->employes->user->name;
            }
        }

        // gabungin nama barang jadi satu string, contoh: "Helm, Sarung Tangan"
        $itemNames = [];
        $totalQty = 0;
        $totalStock = 0;
        $lines = [];

        foreach ($this->transaction_items as $transactionItem) {
            if ($transactionItem->item) {
                $itemNames[] = $transactionItem->item->name;
                $totalStock += $transactionItem->item->current_stock;
            }

            $totalQty += $transactionItem->qty;
            $lines[] = [
                'id' => $transactionItem->id, // id transaction_item: dipakai PATCH /transaction-items/{id}/quantity
                'item_id' => $transactionItem->items_id,
                'item_name' => $transactionItem->item?->name,
                'unit' => $transactionItem->item?->unit,
                'qty' => $transactionItem->qty,
            ];
        }

        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'date' => Carbon::parse($this->date)->translatedFormat('d F Y'),
            'note' => $this->note,
            'employe_name' => $employeName,
            'division' => $division,
            'position' => $position,
            'barang' => implode(', ', $itemNames),
            'total_qty' => $totalQty,
            'total_stock' => $totalStock,
            'items' => $lines,
        ];
    }
}
