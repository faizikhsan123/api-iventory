<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionItemFactory extends Factory
{


    public function definition(): array
    {
        return [
            'transactions_id' => Transaction::inRandomOrder()->value('id'),
            'items_id' => Item::inRandomOrder()->value('id'),
            'qty' => fake()->numberBetween(1, 5),
        ];
    }
}