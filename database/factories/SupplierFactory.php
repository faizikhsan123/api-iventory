<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'pic' => fake()->name(),
            'spesialis' => fake()->randomElement(Supplier::SPESIALIS),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address('20'),
            'email' => fake()->email(),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
