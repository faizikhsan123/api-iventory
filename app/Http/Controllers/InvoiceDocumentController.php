<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsActivity;
use App\Http\Requests\InvoiceDocumentRequest;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InvoiceDocumentController extends Controller
{
    use LogsActivity;

    private const LABELS = ['quote' => 'Quote', 'wo' => 'WO', 'invoice' => 'Invoice'];

    // upload / ganti satu dokumen per jenis; file lama dihapus
    public function store(InvoiceDocumentRequest $request, Invoice $invoice)
    {
        $type = $request->validated('type');
        $file = $request->file('file');
        $disk = Storage::disk(Invoice::DOCUMENT_DISK);

        // nama di storage = hash generate; nama asli hanya disimpan di kolom *_name
        $path = $file->store("invoices/{$invoice->id}", Invoice::DOCUMENT_DISK);
        $originalName = mb_substr($this->safeName($file->getClientOriginalName()), 0, 255);

        try {
            $old = DB::transaction(function () use ($invoice, $type, $path, $originalName) {
                $locked = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
                $old = $locked->getAttribute("{$type}_document");

                $locked->update([
                    "{$type}_document" => $path,
                    "{$type}_document_name" => $originalName,
                ]);

                return $old;
            });
        } catch (Throwable $e) {
            $disk->delete($path);

            throw $e;
        }

        // file lama dihapus hanya setelah commit sukses
        if ($old && $old !== $path) {
            $disk->delete($old);
        }

        $invoice->refresh();

        $this->logActivity('Upload Dokumen Invoice', 'Dokumen '.self::LABELS[$type]." invoice {$invoice->service_name} ({$invoice->division}) diunggah");

        return response()->json([
            'status' => 'success',
            'message' => 'Dokumen Berhasil Diunggah',
            'data' => $invoice->fresh(),
        ], 201);
    }

    public function show(Invoice $invoice, string $type)
    {
        $path = $invoice->getAttribute("{$type}_document");
        $disk = Storage::disk(Invoice::DOCUMENT_DISK);

        abort_unless($path && $disk->exists($path), 404, 'Dokumen tidak ditemukan');

        // ekstensi dari file tersimpan (bukan dari klien); basename dari nama asli
        $base = pathinfo($this->safeName($invoice->getAttribute("{$type}_document_name") ?: 'dokumen'), PATHINFO_FILENAME);
        $base = $this->safeName($base);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $name = $ext !== '' ? "{$base}.{$ext}" : $base;

        return $disk->download($path, $name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(Invoice $invoice, string $type)
    {
        $path = $invoice->getAttribute("{$type}_document");
        abort_unless($path, 404, 'Dokumen tidak ditemukan');

        // DB dulu, baru file: kalau update gagal, file tidak yatim
        $invoice->update(["{$type}_document" => null, "{$type}_document_name" => null]);
        Storage::disk(Invoice::DOCUMENT_DISK)->delete($path);

        $this->logActivity('Menghapus Dokumen Invoice', 'Dokumen '.self::LABELS[$type]." invoice {$invoice->service_name} ({$invoice->division}) dihapus");

        return response()->json([
            'status' => 'success',
            'message' => 'Dokumen Berhasil Dihapus',
            'data' => $invoice->fresh(),
        ]);
    }

    // buang path, karakter kontrol, kutip, dan karakter non-aman untuk header
    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\p{L}\p{N}._ \-()]/u', '_', $name) ?? '';
        $name = ltrim(trim($name), '.');

        return $name !== '' ? $name : 'dokumen';
    }
}
