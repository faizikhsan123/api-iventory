<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class EmployeeCpdTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function cpd(): array
    {
        return [
            'date_of_birth' => '1990-05-17',
            'place_of_birth' => 'Jakarta',
            'gender' => 'Male',
            'nik_ktp' => '3171234567890001',
            'npwp' => '12.345.678.9-012.000',
            'phone' => '081234567890',
            'city' => 'Timika',
        ];
    }

    public function test_admin_can_upsert_and_read_sensitive_fields_encrypted_at_rest(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $this->putJson("/api/employes/{$e->id}/cpd", $this->cpd())
            ->assertOk()
            ->assertJsonPath('data.nik_ktp', '3171234567890001');

        // update kedua = upsert, tetap satu baris
        $this->putJson("/api/employes/{$e->id}/cpd", ['city' => 'Jakarta'])->assertOk();
        $this->assertDatabaseCount('employee_cpd', 1);

        $raw = DB::table('employee_cpd')->where('employes_id', $e->id)->first();
        $this->assertNotSame('3171234567890001', $raw->nik_ktp);
        $this->assertNotSame('12.345.678.9-012.000', $raw->npwp);

        $this->getJson("/api/employes/{$e->id}/cpd")
            ->assertOk()
            ->assertJsonPath('data.npwp', '12.345.678.9-012.000')
            ->assertJsonPath('data.city', 'Jakarta');
    }

    public function test_activity_log_never_contains_nik_or_npwp_values(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $this->putJson("/api/employes/{$e->id}/cpd", $this->cpd())->assertOk();

        $details = DB::table('activities')->pluck('detail')->implode(' ');
        $this->assertStringNotContainsString('3171234567890001', $details);
        $this->assertStringNotContainsString('12.345.678.9', $details);
    }

    public function test_non_admin_cannot_see_nik_npwp_keys_and_cannot_write(): void
    {
        $e = $this->makeEmployee();
        $e->cpd()->create($this->cpd());

        $this->actingAsViewer();

        $res = $this->getJson("/api/employes/{$e->id}/cpd")->assertOk();
        $res->assertJsonPath('data.city', 'Timika');
        $this->assertArrayNotHasKey('nik_ktp', $res->json('data'));
        $this->assertArrayNotHasKey('npwp', $res->json('data'));

        $this->putJson("/api/employes/{$e->id}/cpd", ['city' => 'X'])->assertForbidden();

        $detail = $this->getJson("/api/employes/{$e->id}/detail")->assertOk();
        $this->assertArrayNotHasKey('nik_ktp', $detail->json('data.employe.cpd'));
    }

    public function test_cpd_validation(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $this->putJson("/api/employes/{$e->id}/cpd", ['date_of_birth' => 'bukan-tanggal', 'phone' => str_repeat('1', 40)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth', 'phone']);
    }

    public function test_store_employee_with_cpd_and_ppe(): void
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/employes', [
            'id_number' => 'EMP90001',
            'name' => 'Budi Santoso',
            'division' => 'Safety',
            'position' => 'Technician',
            'contract_start' => '2026-01-01',
            'contract_end' => '2026-12-31',
            'ppe_shoes' => '42',
            'ppe_gloves' => 'L',
            'cpd' => $this->cpd(),
        ])->assertCreated();

        $res->assertJsonPath('data.ppe_sizes.shoes', '42')
            ->assertJsonPath('data.ppe_sizes.gloves', 'L')
            ->assertJsonPath('data.ppe_sizes.vest', null)
            ->assertJsonPath('data.cpd.city', 'Timika');
        $this->assertDatabaseHas('employee_cpd', ['employes_id' => $res->json('data.id')]);
    }

    public function test_store_rolls_back_when_cpd_fails_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/employes', [
            'id_number' => 'EMP90002',
            'name' => 'Budi Santoso',
            'division' => 'Dryer',
            'position' => 'Technician',
            'contract_start' => '2026-01-01',
            'contract_end' => '2026-12-31',
            'ppe_shoes' => str_repeat('9', 31),
        ])->assertStatus(422)->assertJsonValidationErrors('ppe_shoes');

        $this->assertDatabaseMissing('employes', ['id_number' => 'EMP90002']);
    }

    public function test_update_keeps_ppe_when_not_sent_and_updates_cpd(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['ppe_shoes' => '41']);

        $this->putJson("/api/employes/{$e->id}", [
            'id_number' => $e->id_number,
            'name' => 'Budi Santoso',
            'division' => 'Dryer',
            'position' => 'Technician',
            'status' => 'active',
            'ppe_vest' => 'XL',
            'cpd' => ['city' => 'Timika'],
        ])->assertOk()
            ->assertJsonPath('data.ppe_sizes.shoes', '41')
            ->assertJsonPath('data.ppe_sizes.vest', 'XL')
            ->assertJsonPath('data.cpd.city', 'Timika');
    }

    public function test_detail_returns_ppe_sizes_and_trainings_sorted_by_date(): void
    {
        $this->actingAsViewer();
        $e = $this->makeEmployee(['ppe_coverall' => 'L']);
        $t1 = Training::create(['id_training' => 'T-1', 'division_training' => 'Safety', 'name_training' => 'Lama', 'by' => 'A']);
        $t2 = Training::create(['id_training' => 'T-2', 'division_training' => 'Dryer', 'name_training' => 'Baru', 'by' => 'B']);
        TrainingParticipant::create(['training_id' => $t1->id, 'employes_id' => $e->id, 'date' => '2026-01-01']);
        TrainingParticipant::create(['training_id' => $t2->id, 'employes_id' => $e->id, 'date' => '2026-06-01', 'notes' => 'ok']);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $res = $this->getJson("/api/employes/{$e->id}/detail")->assertOk();

        $res->assertJsonPath('data.employe.ppe_sizes.coverall', 'L')
            ->assertJsonCount(2, 'data.trainings')
            ->assertJsonPath('data.trainings.0.name_training', 'Baru')
            ->assertJsonPath('data.trainings.0.date', '2026-06-01')
            ->assertJsonPath('data.trainings.0.division_training', 'Dryer')
            ->assertJsonPath('data.trainings.1.id_training', 'T-1');
        $this->assertArrayHasKey('riwayat_diberikan', $res->json('data'));
        $this->assertLessThan(25, $queries);
    }

    public function test_index_search_by_name_and_no_given_items_count(): void
    {
        $this->actingAsViewer();
        $e = $this->makeEmployee();
        $e->user->update(['name' => 'Zulkifli Unik']);
        $this->makeEmployee();

        $res = $this->getJson('/api/employes?search=Zulkifli')->assertOk();
        $res->assertJsonCount(1, 'data');
        $this->assertArrayNotHasKey('given_items_count', $res->json('data.0'));
        $this->assertArrayHasKey('ppe_sizes', $res->json('data.0'));
    }
}
