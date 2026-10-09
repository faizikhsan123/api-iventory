<?php

namespace Database\Factories;

use App\Models\Employes;
use App\Models\group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_group' => $this->faker->word(),
            
            'employes_id' => Employes::inRandomOrder()->value('id'),
        ];
    }
}
