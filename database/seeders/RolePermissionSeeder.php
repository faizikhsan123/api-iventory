<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // buat permission
        $permissions = ['tambah_user', 'edit_user', 'hapus_user', 'lihat_user'];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // buat role
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'office']); // read-only di semua modul

        // assign semua permission ke admin
        $roleAdmin->syncPermissions($permissions);
    }
}
