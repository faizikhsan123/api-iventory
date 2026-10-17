<?php

namespace Database\Seeders;

use App\Models\Employes;
use Illuminate\Database\Seeder;

class EmployesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employes::factory(10)->create();
    }
}
