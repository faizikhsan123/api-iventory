<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

// QA integrasi: kontrak yang dipakai frontend (CPD, detail karyawan, MCU multipart)
class IntegrationQaTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private const JSON = ['Accept' => 'application/json'];

    public function test_cpd_put_json_as_frontend_sends_and_empty_string_clears(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $this->putJson("/api/employes/{$e->id}/cpd", ['city' => 'Timika', 'phone' => '0812', 'date_of_birth' => '1990-01-02', 'nik_ktp' => '123'])->assertOk();
        // form edit mengirim semua field, yang kosong "" harus mengosongkan kolom
        $this->putJson("/api/employes/{$e->id}/cpd", ['city' => '', 'phone' => '0899', 'date_of_birth' => '', 'nik_ktp' => ''])
            ->assertOk()
            ->assertJsonPath('data.city', null)
            ->assertJsonPath('data.phone', '0899')
            ->assertJsonPath('data.date_of_birth', null)
            ->assertJsonPath('data.nik_ktp', null);
    }

    public function test_detail_shape_cpd_and_ppe_under_employe_and_nik_hidden_for_non_admin(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['ppe_shoes' => '42']);
        $this->putJson("/api/employes/{$e->id}/cpd", ['city' => 'Timika', 'nik_ktp' => '999', 'npwp' => '888'])->assertOk();

        $this->getJson("/api/employes/{$e->id}/detail")
            ->assertOk()
            ->assertJsonPath('data.employe.cpd.nik_ktp', '999')
            ->assertJsonPath('data.employe.ppe_sizes.shoes', '42')
            ->assertJsonStructure(['data' => ['mcus', 'trainings', 'statistik', 'riwayat_diberikan']]);

        $this->actingAsViewer();
        $res = $this->getJson("/api/employes/{$e->id}/detail")->assertOk();
        $res->assertJsonPath('data.employe.cpd.city', 'Timika');
        $this->assertArrayNotHasKey('nik_ktp', $res->json('data.employe.cpd'));
        $this->assertArrayNotHasKey('npwp', $res->json('data.employe.cpd'));
        $this->assertStringNotContainsString('999', $res->getContent());
        $this->assertStringNotContainsString('888', $res->getContent());
        // show & list juga tidak membocorkan
        $this->assertStringNotContainsString('999', $this->getJson("/api/employes/{$e->id}")->getContent());
        $this->assertStringNotContainsString('999', $this->getJson('/api/employes')->getContent());
        $this->putJson("/api/employes/{$e->id}/cpd", ['city' => 'X'])->assertForbidden();
    }

    public function test_old_cpd_route_via_employee_update_needs_required_fields(): void
    {
        // dokumentasi: update karyawan tidak boleh dipakai untuk simpan CPD saja
        $this->actingAsAdmin();
        $e = $this->makeEmployee();
        $this->patchJson("/api/employes/{$e->id}", ['cpd' => ['city' => 'X']])->assertStatus(422);
    }

    public function test_mcu_multipart_patch_via_post_remove_flag_one(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $e = $this->makeEmployee();
        $base = ['employes_id' => $e->id, 'place_name' => 'RS', 'mcu_date' => '2026-10-01'];

        $c = $this->post('/api/mcus', $base + [
            'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            'document_2' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ], self::JSON)->assertCreated();
        $id = $c->json('data.id');
        $d1 = $c->json('data.document');
        $d2 = $c->json('data.document_2');

        $r = $this->post("/api/mcus/{$id}", $base + ['_method' => 'PATCH', 'remove_document_2' => '1'], self::JSON)->assertOk();
        $r->assertJsonPath('data.document', $d1)->assertJsonPath('data.document_2', null);
        Storage::disk('public')->assertExists($d1);
        Storage::disk('public')->assertMissing($d2);

        $this->post("/api/mcus/{$id}", $base + ['_method' => 'PATCH', 'remove_document' => '1', 'remove_document_2' => '1'], self::JSON)
            ->assertOk()->assertJsonPath('data.document', null);
        Storage::disk('public')->assertMissing($d1);
    }

    public function test_employee_rejects_legacy_divisions(): void
    {
        $this->actingAsAdmin();
        foreach (['PMR', 'ER', 'Gas', 'GA', 'INC-PMR'] as $old) {
            $this->post('/api/employes', ['name' => 'Budi Santoso', 'id_number' => 'X'.rand(1000, 9999), 'division' => $old, 'position' => 'Technician'], self::JSON)
                ->assertStatus(422)->assertJsonValidationErrors('division');
        }
    }
}
