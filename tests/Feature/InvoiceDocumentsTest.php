<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class InvoiceDocumentsTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function makeInvoice(): Invoice
    {
        return Invoice::create([
            'service_name' => 'Kalibrasi', 'service_date' => '2026-10-01', 'division' => 'Dryer',
            'client' => 'PT A', 'amount' => 1000, 'status' => 'drafting_timesheet', 'invoice_date' => '2026-10-05',
        ]);
    }

    private function upload(Invoice $inv, string $type, ?UploadedFile $file = null)
    {
        return $this->post("/api/invoices/{$inv->id}/documents", [
            'type' => $type,
            'file' => $file ?? UploadedFile::fake()->create('Quote A.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);
    }

    public function test_admin_uploads_and_response_hides_paths(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();

        $res = $this->upload($inv, 'quote')->assertCreated()
            ->assertJsonPath('data.documents.quote.name', 'Quote A.pdf')
            ->assertJsonPath('data.documents.quote.has_file', true)
            ->assertJsonPath('data.documents.wo', null)
            ->assertJsonPath('data.documents.invoice', null);

        $this->assertArrayNotHasKey('quote_document', $res->json('data'));
        $path = $inv->fresh()->quote_document;
        $this->assertStringStartsWith("invoices/{$inv->id}/", $path);
        $this->assertStringNotContainsString('Quote', $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_reupload_replaces_and_deletes_old_file(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();

        $this->upload($inv, 'wo');
        $old = $inv->fresh()->wo_document;
        $this->upload($inv, 'wo', UploadedFile::fake()->image('baru.png'))->assertCreated()
            ->assertJsonPath('data.documents.wo.name', 'baru.png');
        $new = $inv->fresh()->wo_document;

        $this->assertNotSame($old, $new);
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($new);
    }

    public function test_invalid_type_mime_size_and_missing_file_rejected(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();

        $this->upload($inv, 'hack')->assertStatus(422)->assertJsonValidationErrors('type');
        $this->upload($inv, '../../etc')->assertStatus(422)->assertJsonValidationErrors('type');
        $this->upload($inv, 'quote', UploadedFile::fake()->create('x.exe', 10))->assertStatus(422)->assertJsonValidationErrors('file');
        $this->upload($inv, 'quote', UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'))->assertStatus(422)->assertJsonValidationErrors('file');
        $this->postJson("/api/invoices/{$inv->id}/documents", ['type' => 'quote'])->assertStatus(422)->assertJsonValidationErrors('file');
        $this->assertNull($inv->fresh()->quote_document);
    }

    public function test_file_of_exactly_10mb_is_accepted(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();

        $this->upload($inv, 'quote', UploadedFile::fake()->create('ten.pdf', 10240, 'application/pdf'))->assertCreated();
        $this->assertNotNull($inv->fresh()->quote_document);
    }

    public function test_non_admin_cannot_upload_or_delete_but_can_download(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();
        $this->upload($inv, 'invoice');

        $this->actingAsViewer();
        $this->upload($inv, 'quote')->assertForbidden();
        $this->deleteJson("/api/invoices/{$inv->id}/documents/invoice")->assertForbidden();

        $this->get("/api/invoices/{$inv->id}/documents/invoice")->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotNull($inv->fresh()->invoice_document);
    }

    public function test_download_filename_is_sanitized(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();
        $this->upload($inv, 'quote', UploadedFile::fake()->create('..\\..\\evil";x.pdf', 10, 'application/pdf'));

        $disp = $this->get("/api/invoices/{$inv->id}/documents/quote")->assertOk()->headers->get('Content-Disposition');
        $this->assertStringNotContainsString('..', $disp);
        $this->assertStringNotContainsString('\\', $disp);
        $this->assertStringContainsString('attachment', $disp);
        $this->assertStringNotContainsString('"x', $disp);
    }

    public function test_download_extension_comes_from_stored_file_not_client(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();
        $this->upload($inv, 'quote', UploadedFile::fake()->create('laporan.pdf', 10, 'application/pdf'));
        $ext = pathinfo($inv->fresh()->quote_document, PATHINFO_EXTENSION);

        // nama tersimpan berekstensi aneh; ekstensi unduhan harus tetap dari path tersimpan
        $inv->update(['quote_document_name' => 'laporan.html']);

        $disp = $this->get("/api/invoices/{$inv->id}/documents/quote")->assertOk()->headers->get('Content-Disposition');
        $this->assertStringContainsString("laporan.{$ext}", $disp);
        $this->assertStringNotContainsString('.html', $disp);
    }

    public function test_invoice_store_and_update_ignore_document_fields(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $evil = [
            'quote_document' => 'invoices/999/evil.pdf', 'quote_document_name' => 'evil.pdf',
            'wo_document' => '../../.env', 'wo_document_name' => 'x',
            'invoice_document' => 'invoices/1/y.pdf', 'invoice_document_name' => 'y',
        ];

        $id = $this->postJson('/api/invoices', [
            'service_name' => 'Kalibrasi', 'division' => 'Dryer', ...$evil,
        ])->assertCreated()->json('data.id');

        $inv = Invoice::findOrFail($id);
        foreach (array_keys($evil) as $col) {
            $this->assertNull($inv->getAttribute($col), "store menerima {$col}");
        }

        $this->putJson("/api/invoices/{$id}", ['service_name' => 'Baru', 'division' => 'Dryer', ...$evil])->assertOk();
        $inv = $inv->fresh();
        $this->assertSame('Baru', $inv->service_name);
        foreach (array_keys($evil) as $col) {
            $this->assertNull($inv->getAttribute($col), "update menerima {$col}");
        }
    }

    public function test_unauthenticated_gets_401(): void
    {
        $inv = $this->makeInvoice();

        $this->getJson("/api/invoices/{$inv->id}/documents/quote")->assertUnauthorized();
        $this->postJson("/api/invoices/{$inv->id}/documents", [])->assertUnauthorized();
        $this->deleteJson("/api/invoices/{$inv->id}/documents/quote")->assertUnauthorized();
    }

    public function test_unknown_type_or_missing_file_returns_404(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();

        $this->getJson("/api/invoices/{$inv->id}/documents/passwd")->assertNotFound();
        $this->deleteJson("/api/invoices/{$inv->id}/documents/passwd")->assertNotFound();
        $this->getJson("/api/invoices/{$inv->id}/documents/quote")->assertNotFound();
        $this->deleteJson("/api/invoices/{$inv->id}/documents/quote")->assertNotFound();
    }

    public function test_delete_removes_file_and_clears_columns(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();
        $this->upload($inv, 'quote');
        $path = $inv->fresh()->quote_document;

        $this->deleteJson("/api/invoices/{$inv->id}/documents/quote")->assertOk()
            ->assertJsonPath('data.documents.quote', null);

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($inv->fresh()->quote_document_name);
    }

    public function test_index_show_expose_documents_and_destroy_invoice_removes_files(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $inv = $this->makeInvoice();
        $this->upload($inv, 'quote');
        $path = $inv->fresh()->quote_document;

        $this->getJson('/api/invoices')->assertOk()->assertJsonPath('data.0.documents.quote.has_file', true);
        $this->getJson("/api/invoices/{$inv->id}")->assertOk()
            ->assertJsonPath('data.documents.quote.has_file', true)
            ->assertJsonMissingPath('data.quote_document');

        $this->deleteJson("/api/invoices/{$inv->id}")->assertOk();
        Storage::disk('local')->assertMissing($path);
    }
}
