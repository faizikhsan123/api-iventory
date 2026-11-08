<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoicePeriodMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // DDL commits implicitly on MySQL; isolate this schema test from transactional tests.
        $this->assertSame('api-iventory_test', DB::connection()->getDatabaseName());
        RefreshDatabaseState::$migrated = false;
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_adding_period_columns_preserves_existing_invoices_documents_and_logs(): void
    {
        $migration = require database_path('migrations/2026_11_08_000001_add_work_period_to_invoices.php');
        $migration->down();
        $invoice = Invoice::create([
            'service_name' => 'Existing W22', 'division' => 'Dryer', 'amount' => 150000,
            'invoice_date' => '2026-08-14', 'service_date' => '2026-06-01',
            'quote_document' => 'invoices/1/quote.pdf', 'quote_document_name' => 'Old quote.pdf',
        ]);
        $invoice->logs()->create(['status' => 'drafting_timesheet', 'status_date' => '2026-08-14', 'note' => 'Existing log']);
        $before = (array) DB::table('invoices')->where('id', $invoice->id)->first();
        $logsBefore = DB::table('invoice_status_logs')->get()->toArray();

        $migration->up();

        $after = (array) DB::table('invoices')->where('id', $invoice->id)->first();
        foreach ($before as $key => $value) {
            $this->assertSame($value, $after[$key], "Existing column {$key} changed");
        }
        foreach (['contract_number', 'work_period_start', 'work_period_end', 'week_number'] as $key) {
            $this->assertArrayHasKey($key, $after);
            $this->assertNull($after[$key]);
        }
        $this->assertEquals($logsBefore, DB::table('invoice_status_logs')->get()->toArray());
        $this->assertDatabaseCount('invoices', 1);
    }
}
