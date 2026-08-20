<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'name' => fake()->words(3, true),

            'part_number' => 'ITM-' . fake()->unique()->numberBetween(100000, 999999),

            'file' => null,

            'current_stock' => fake()->numberBetween(0, 100),

            'status' => fake()->randomElement([
                'available',
                'low_stock',
                'out_of_stock',
            ]),

            'min_stock' => fake()->numberBetween(1, 20),

            'category' => fake()->randomElement([
                'apd',
                'tools',
            ]),

            'brand' => fake()->randomElement([
                'Krisbow',
                'Tekiro',
                'Bosch',
                '3M',
                'Safety Jogger',
            ]),

            'type' => fake()->words(2, true),

            'size' => fake()->randomElement([
                'S',
                'M',
                'L',
                'XL',
                'XXL',
            ]),

            'unit' => fake()->randomElement([
                'pcs',
                'set',
                'unit',
                'pair',
            ]),

            'description' => fake()->sentence(),
        ];
    }
}
