<?php

namespace Database\Seeders;

use App\Models\TransactionItem;
use Illuminate\Database\Seeder;

class TransactionItemSeeder extends Seeder
{
    public function run(): void
    {
        TransactionItem::factory(10)->create();
    }
}