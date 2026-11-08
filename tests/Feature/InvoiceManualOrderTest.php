<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class InvoiceManualOrderTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'service_name' => 'Daily Work PMR', 'division' => 'I&C-PMR',
            'service_date' => '2026-08-31', 'invoice_date' => '2026-09-08',
            'amount' => 39924450,
        ], $overrides);
    }

    public function test_manual_order_is_numeric_and_applied_before_pagination(): void
    {
        $this->actingAsViewer();
        foreach ([22, 9, 18, 2] as $order) {
            Invoice::create($this->payload(['sort_order' => $order]));
        }
        $legacy = Invoice::create($this->payload(['service_date' => '2020-01-01']));
        $values = [];
        foreach ([1, 2, 3] as $page) {
            $res = $this->getJson("/api/invoices?per_page=2&page={$page}")->assertOk();
            $values = [...$values, ...array_column($res->json('data'), 'sort_order')];
        }
        $this->assertSame([2, 9, 18, 22, null], $values);
        $desc = $this->getJson('/api/invoices?sort=desc')->assertOk();
        $this->assertSame([22, 18, 9, 2, null], array_column($desc->json('data'), 'sort_order'));
        $this->assertSame($legacy->id, $desc->json('data.4.id'));
    }

    public function test_editing_order_moves_old_invoice_without_changing_other_data(): void
    {
        $this->actingAsAdmin();
        $april = Invoice::create($this->payload(['service_name' => 'APR 2026 W17', 'sort_order' => 10]));
        $june = Invoice::create($this->payload(['service_name' => 'JUN 2026 W23-W26', 'sort_order' => 30]));
        $may = Invoice::create($this->payload([
            'service_name' => 'MAY 2026 W18-W22', 'service_date' => '2026-09-07',
            'sort_order' => 40, 'quote_document' => 'invoices/old/quote.pdf',
            'quote_document_name' => 'Existing.pdf', 'notes' => 'Keep existing notes',
        ]));
        $may->logs()->create(['status' => 'drafting_timesheet', 'status_date' => '2026-09-17', 'note' => 'Existing history']);
        DB::table('invoices')->where('id', $may->id)->update(['contract_number' => 'Old contract', 'week_number' => 18]);
        $before = (array) DB::table('invoices')->where('id', $may->id)->first();

        $this->putJson("/api/invoices/{$may->id}", [
            'service_name' => $may->service_name, 'division' => $may->division, 'sort_order' => 20,
        ])->assertOk()->assertJsonPath('data.sort_order', 20);

        $this->assertSame([$april->id, $may->id, $june->id], array_column($this->getJson('/api/invoices')->assertOk()->json('data'), 'id'));
        $after = (array) DB::table('invoices')->where('id', $may->id)->first();
        foreach (array_diff_key($before, array_flip(['sort_order', 'updated_at'])) as $key => $value) {
            $this->assertSame($value, $after[$key], "Unexpected change to {$key}");
        }
        $this->assertSame(1, $may->logs()->count());
        $this->getJson("/api/invoices/{$may->id}")->assertOk()
            ->assertJsonPath('data.sort_order', 20)->assertJsonPath('data.documents.quote.name', 'Existing.pdf');
    }

    public function test_admin_can_create_clear_or_omit_order(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/api/invoices', $this->payload(['sort_order' => '12']))
            ->assertCreated()->assertJsonPath('data.sort_order', 12)->json('data.id');
        $this->putJson("/api/invoices/{$id}", $this->payload())->assertOk()->assertJsonPath('data.sort_order', 12);
        $this->putJson("/api/invoices/{$id}", $this->payload(['sort_order' => null]))
            ->assertOk()->assertJsonPath('data.sort_order', null);
        $this->postJson('/api/invoices', $this->payload())->assertCreated()->assertJsonPath('data.sort_order', null);
        $this->assertDatabaseCount('invoices', 2);
    }

    public function test_duplicate_order_has_deterministic_fallback_and_no_invoice_is_lost(): void
    {
        $this->actingAsViewer();
        $later = Invoice::create($this->payload(['sort_order' => 10, 'service_date' => '2026-06-01']));
        $earlier = Invoice::create($this->payload(['sort_order' => 10, 'service_date' => '2026-04-01']));
        $tie = Invoice::create($this->payload(['sort_order' => 10, 'service_date' => '2026-04-01']));
        $this->assertSame([$earlier->id, $tie->id, $later->id], array_column($this->getJson('/api/invoices')->assertOk()->json('data'), 'id'));
    }

    public function test_order_is_independent_of_description_week_division_and_status(): void
    {
        $this->actingAsViewer();
        Invoice::create($this->payload(['service_name' => 'W1 JAN', 'sort_order' => 20]));
        Invoice::create($this->payload(['service_name' => 'W99 DEC', 'sort_order' => 10, 'division' => 'Gas Analyzer', 'status' => 'paid']));
        $this->getJson('/api/invoices?sort_status=asc')->assertOk()->assertJsonPath('data.0.sort_order', 10);
        $this->getJson('/api/invoices?division=I%26C-PMR')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.sort_order', 20);
    }

    public function test_order_rejects_non_positive_fractional_and_out_of_range_values(): void
    {
        $this->actingAsAdmin();
        foreach ([0, -1, 1.5, 'W18', 2147483648] as $order) {
            $this->postJson('/api/invoices', $this->payload(['sort_order' => $order]))
                ->assertUnprocessable()->assertJsonValidationErrors('sort_order');
        }
    }

    public function test_office_can_read_order_but_cannot_change_it(): void
    {
        $invoice = Invoice::create($this->payload(['sort_order' => 10]));
        $this->actingAsOffice();
        $this->getJson('/api/invoices')->assertOk()->assertJsonPath('data.0.sort_order', 10);
        $this->putJson("/api/invoices/{$invoice->id}", $this->payload(['sort_order' => 20]))->assertForbidden();
        $this->assertSame(10, $invoice->fresh()->sort_order);
    }
}
