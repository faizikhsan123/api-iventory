<?php

namespace Database\Factories;

use App\Models\Employes;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_number' => 'TRX-' . fake()->unique()->numberBetween(100000, 999999),
            'employes_id' => Employes::inRandomOrder()->value('id'),
           
        ];
    }
}
