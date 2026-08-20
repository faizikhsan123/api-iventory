<?php

namespace Database\Factories;

use App\Models\Employes;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employes>
 */
class EmployesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'user_id' => User::inRandomOrder()->value('id'),

            'division' => fake()->randomElement([
                'GA',
                'HRD',
                'IT',
                'Finance',
                'Produksi',
                'Warehouse',
            ]),

            'position' => fake()->randomElement([
                'Staff',
                'Supervisor',
                'Manager',
                'Admin',
                'Operator',
                'Teknisi',
            ]),

            'status' => fake()->randomElement([
                'active',
                'inactive',
            ]),
        ];
    }
}
