<?php

namespace Tests\Feature;

use App\Models\Mcu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class McuDocumentsTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function base(int $employeeId): array
    {
        return ['employes_id' => $employeeId, 'place_name' => 'RS A', 'mcu_date' => '2026-10-01'];
    }

    public function test_store_with_two_documents(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $res = $this->post('/api/mcus', $this->base($e->id) + [
            'document' => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            'document_2' => UploadedFile::fake()->image('b.png'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $d1 = $res->json('data.document');
        $d2 = $res->json('data.document_2');
        $this->assertNotNull($d1);
        $this->assertNotNull($d2);
        Storage::disk('public')->assertExists([$d1, $d2]);
    }

    public function test_document_2_validation(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $this->post('/api/mcus', $this->base($e->id) + [
            'document_2' => UploadedFile::fake()->create('x.exe', 10),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('document_2');

        $this->post('/api/mcus', $this->base($e->id) + [
            'document_2' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('document_2');
    }

    public function test_update_replaces_keeps_and_removes_each_file_independently(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $created = $this->post('/api/mcus', $this->base($e->id) + [
            'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            'document_2' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $id = $created->json('data.id');
        $old1 = $created->json('data.document');
        $old2 = $created->json('data.document_2');

        // ganti dokumen 2 saja; dokumen 1 tetap
        $r = $this->post("/api/mcus/{$id}", $this->base($e->id) + [
            '_method' => 'PUT',
            'document_2' => UploadedFile::fake()->create('c.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $new2 = $r->json('data.document_2');
        $this->assertSame($old1, $r->json('data.document'));
        $this->assertNotSame($old2, $new2);
        Storage::disk('public')->assertMissing($old2);
        Storage::disk('public')->assertExists([$old1, $new2]);

        // hapus dokumen 1 saja
        $r = $this->putJson("/api/mcus/{$id}", $this->base($e->id) + ['remove_document' => true])->assertOk();
        $this->assertNull($r->json('data.document'));
        $this->assertSame($new2, $r->json('data.document_2'));
        Storage::disk('public')->assertMissing($old1);
        Storage::disk('public')->assertExists($new2);

        // hapus dokumen 2
        $r = $this->putJson("/api/mcus/{$id}", $this->base($e->id) + ['remove_document_2' => true])->assertOk();
        $this->assertNull($r->json('data.document_2'));
        Storage::disk('public')->assertMissing($new2);
    }

    public function test_destroy_deletes_both_files(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $e = $this->makeEmployee();

        $created = $this->post('/api/mcus', $this->base($e->id) + [
            'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            'document_2' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->deleteJson('/api/mcus/'.$created->json('data.id'))->assertOk();

        Storage::disk('public')->assertMissing([$created->json('data.document'), $created->json('data.document_2')]);
        $this->assertSame(0, Mcu::count());
    }

    public function test_non_admin_cannot_write(): void
    {
        $this->actingAsViewer();
        $e = $this->makeEmployee();

        $this->postJson('/api/mcus', $this->base($e->id))->assertForbidden();
    }
}
