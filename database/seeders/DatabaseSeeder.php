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
            // UserSeeder::class,
            // EmployesSeeder::class,
            // groupSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'admin', 'password' => bcrypt('password')]
        );

        $admin->assignRole('admin');

        // office: read-only. updateOrCreate agar idempotent & password selalu sinkron.
        $office = User::updateOrCreate(
            ['email' => 'office@gmail.com'],
            ['name' => 'office', 'password' => bcrypt('password')]
        );

        $office->syncRoles('office');
    }
}
