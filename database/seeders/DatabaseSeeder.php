<?php

namespace Database\Seeders;

use App\Models\Transaction;
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

        // Admin dari env (ADMIN_EMAIL / ADMIN_PASSWORD). Tanpa env = tidak dibuat.
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email && $password) {
            $admin = User::updateOrCreate(
                ['email' => $email],
                ['name' => 'admin', 'password' => bcrypt($password)]
            );
            $admin->syncRoles('admin');
        } elseif (app()->isProduction()) {
            $this->command?->warn('ADMIN_EMAIL/ADMIN_PASSWORD kosong: tidak ada akun admin dibuat.');
        }

        // Akun default hanya untuk lokal/testing, TIDAK di production.
        if (! app()->isProduction()) {
            $localAdmin = User::firstOrCreate(
                ['email' => 'admin@gmail.com'],
                ['name' => 'admin', 'password' => bcrypt('password')]
            );
            $localAdmin->assignRole('admin');

            $office = User::updateOrCreate(
                ['email' => 'office@gmail.com'],
                ['name' => 'office', 'password' => bcrypt('password')]
            );
            $office->syncRoles('office');

            
        }
    }
}
