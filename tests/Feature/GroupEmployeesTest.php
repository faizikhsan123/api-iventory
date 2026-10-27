<?php

namespace Tests\Feature;

use App\Models\Employes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class GroupEmployeesTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    public function test_all_employee_pages_include_every_employee_with_equal_creation_times(): void
    {
        $this->actingAsAdmin();
        $this->freezeTime();
        $employees = collect(range(1, 105))->map(fn () => $this->makeEmployee());

        $first = $this->getJson('/api/employes?per_page=100&page=1')
            ->assertOk()->assertJsonCount(100, 'data')->assertJsonPath('meta.last_page', 2);
        $second = $this->getJson('/api/employes?per_page=100&page=2')
            ->assertOk()->assertJsonCount(5, 'data');

        $ids = array_merge(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id'));
        $this->assertCount(105, array_unique($ids));
        $this->assertSame($employees->pluck('id')->sortDesc()->values()->all(), $ids);
    }

    public function test_group_can_select_employees_beyond_the_first_default_page_and_preserve_members(): void
    {
        $this->actingAsAdmin();
        $employees = collect(range(1, 15))->map(fn () => $this->makeEmployee());
        $ids = $employees->pluck('id')->all();

        $created = $this->postJson('/api/groups', ['name_group' => 'Semua Karyawan', 'employes_ids' => $ids])
            ->assertCreated()->assertJsonCount(15, 'data.employees');
        $id = $created->json('data.id');

        $this->getJson("/api/groups/{$id}")->assertOk()->assertJsonCount(15, 'data.employees');
        $this->putJson("/api/groups/{$id}", ['name_group' => 'Nama Baru', 'employes_ids' => $ids])
            ->assertOk()->assertJsonCount(15, 'data.employees');
        $this->assertSame(15, Employes::where('group_id', $id)->count());
    }
}
