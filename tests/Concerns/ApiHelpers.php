<?php

namespace Tests\Concerns;

use App\Models\Employes;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

trait ApiHelpers
{
    protected function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsViewer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('viewer', 'web'));
        Sanctum::actingAs($user);

        return $user;
    }

    protected function makeEmployee(array $attrs = []): Employes
    {
        $user = User::factory()->create();

        return Employes::create(array_merge([
            'user_id' => $user->id,
            'id_number' => 'EMP'.fake()->unique()->numberBetween(1000, 99999),
            'division' => 'Dryer',
            'position' => 'Technician',
            'status' => 'active',
        ], $attrs));
    }
}
