<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MigrateStaffToOffice extends Command
{
    protected $signature = 'roles:staff-to-office {--password= : Password akun office@gmail.com (wajib)}';

    protected $description = 'Rename role staff menjadi office (assignment dipertahankan) dan upsert akun office@gmail.com. Idempotent.';

    public function handle(): int
    {
        $password = (string) $this->option('password');
        if ($password === '') {
            $this->error('Opsi --password wajib diisi.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($password) {
            $staff = Role::where('name', 'staff')->where('guard_name', 'web')->first();
            $office = Role::where('name', 'office')->where('guard_name', 'web')->first();

            if ($staff && ! $office) {
                $staff->update(['name' => 'office']);
                $office = $staff;
                $this->info('Role staff di-rename menjadi office.');
            } elseif ($staff && $office) {
                // keduanya ada: pindahkan assignment ke office lalu hapus staff
                foreach (User::role('staff')->get() as $u) {
                    $u->assignRole('office');
                    $u->removeRole('staff');
                }
                $staff->delete();
                $this->info('Assignment staff dipindah ke office, role staff dihapus.');
            } elseif (! $office) {
                Role::create(['name' => 'office', 'guard_name' => 'web']);
                $this->info('Role office dibuat.');
            }

            $user = User::updateOrCreate(
                ['email' => 'office@gmail.com'],
                ['name' => 'office', 'password' => Hash::make($password)]
            );
            $user->syncRoles('office');
            $this->info('Akun office@gmail.com siap (role office).');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return self::SUCCESS;
    }
}
