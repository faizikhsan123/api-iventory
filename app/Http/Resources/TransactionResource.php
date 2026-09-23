<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        // ambil data karyawan
        $employeName = null;
        $division = null;
        $position = null;

        // jika ada employes
        if ($this->employes) {
            $division = $this->employes->division;
            $position = $this->employes->position;

            // masukkan relasi antara user dan employe untuk name
            if ($this->employes->user) {
                $employeName = $this->employes->user->name;
            }
        }

        // gabungin nama barang jadi satu string, contoh: "Helm, Sarung Tangan"
        $itemNames = [];
        $totalQty = 0;

        foreach ($this->transaction_items as $transactionItem) {
            if ($transactionItem->item) {
                $itemNames[] = $transactionItem->item->name;
            }
            $totalQty += $transactionItem->qty;
        }

        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'date' => $this->date,
            'note' => $this->note,
            'employe_name' => $employeName,
            'division' => $division,
            'position' => $position,
            'barang' => implode(', ', $itemNames),
            'total_qty' => $totalQty,
        ];
    }
}