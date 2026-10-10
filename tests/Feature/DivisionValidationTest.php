<?php

namespace Tests\Feature;

use App\Enums\Division;
use App\Models\Training;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class DivisionValidationTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    public static function newDivisions(): array
    {
        return [['Dryer'], ['Safety']];
    }

    private function employeePayload(string $division): array
    {
        return [
            'id_number' => 'EMP'.random_int(10000, 99999),
            'name' => 'Budi Santoso',
            'division' => $division,
            'position' => 'Technician',
            'contract_start' => '2026-01-01',
            'contract_end' => '2026-12-31',
        ];
    }

    public function test_division_enum_values(): void
    {
        $this->assertSame(['Gas Analyzer', 'I&C-PMR', 'I&C-ER', 'Dryer', 'Safety'], Division::values());
    }

    #[DataProvider('newDivisions')]
    public function test_employee_accepts_new_divisions(string $division): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/employes', $this->employeePayload($division))
            ->assertCreated()
            ->assertJsonPath('data.division', $division);
    }

    public function test_employee_rejects_unknown_division(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/employes', $this->employeePayload('GA'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('division');
    }

    public function test_employee_update_accepts_dryer(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['division' => 'Gas Analyzer']);

        $this->putJson("/api/employes/{$e->id}", [
            'id_number' => $e->id_number,
            'name' => 'Budi Santoso',
            'division' => 'Dryer',
            'position' => 'Technician',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('data.division', 'Dryer');
    }

    #[DataProvider('newDivisions')]
    public function test_training_accepts_new_divisions(string $division): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/trainings', [
            'id_training' => 'T-1',
            'division_training' => $division,
            'name_training' => 'K3',
            'by' => 'Vendor',
        ])->assertCreated();

        $this->assertDatabaseHas('trainings', ['division_training' => $division]);
    }

    public function test_training_rejects_unknown_division_on_store_and_update(): void
    {
        $this->actingAsAdmin();
        $t = Training::create(['id_training' => 'T-2', 'division_training' => 'Safety', 'name_training' => 'X', 'by' => 'Y']);

        $this->postJson('/api/trainings', [
            'id_training' => 'T-3',
            'division_training' => 'Nope',
            'name_training' => 'K3',
            'by' => 'V',
        ])->assertStatus(422)->assertJsonValidationErrors('division_training');

        $this->putJson("/api/trainings/{$t->id}", ['division_training' => 'Nope'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('division_training');

        $this->putJson("/api/trainings/{$t->id}", ['division_training' => 'Dryer'])->assertSuccessful();
    }
}
