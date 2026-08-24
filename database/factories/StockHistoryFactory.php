<?php

namespace Database\Factories;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
/**
 * @extends Factory<StockHistory>
 */
class StockHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::inRandomOrder()->value('id'),
            'supplier_id' => Supplier::inRandomOrder()->value('id'),
            'user_id' => User::inRandomOrder()->value('id'),
            'note' => fake()->sentence(2),
            'type' => fake()->randomElement(['in', 'out']),
            'qty' => fake()->randomNumber(2),
        ];
    }
}
