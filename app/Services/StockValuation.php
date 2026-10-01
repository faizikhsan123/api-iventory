<?php

namespace App\Services;

use App\Models\Item;

class StockValuation
{
    /** Panggil SEBELUM current_stock ditambah. Return snapshot harga. */
    public static function applyIn(Item $item, int $qty): float
    {
        $price = (float) $item->price;
        $oldStock = (int) $item->current_stock;
        $newStock = $oldStock + $qty;

        $item->avg_price = $newStock > 0
            ? (($oldStock * (float) $item->avg_price) + ($qty * $price)) / $newStock
            : $price;

        return $price;
    }

    /** Stok keluar: avg nggak berubah. */
    public static function snapshotOut(Item $item): float
    {
        return (float) $item->avg_price;
    }
}