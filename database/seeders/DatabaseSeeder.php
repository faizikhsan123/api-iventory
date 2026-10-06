<?php

namespace Database\Seeders;

use App\Models\Employes;
use App\Models\group;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // urutan penting: role harus ada dulu sebelum di-assign
        $this->call([
            RolePermissionSeeder::class,
            // UserSeeder::class,
            // EmployesSeeder::class,
            // groupSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'admin', 'password' => bcrypt('password')]
        );

        $admin->assignRole('admin');

        $admin = User::firstOrCreate(
            ['email' => 'prita@gmail.com'],
            ['name' => 'prita', 'password' => bcrypt('password')]
        );

        $admin->assignRole('staff');

     

       
    }
}