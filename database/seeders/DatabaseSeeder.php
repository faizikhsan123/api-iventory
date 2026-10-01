<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // urutan penting: role harus ada dulu sebelum di-assign
        $this->call([
            RolePermissionSeeder::class,
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