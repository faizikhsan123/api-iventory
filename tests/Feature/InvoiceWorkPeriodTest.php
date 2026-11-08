<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class InvoiceWorkPeriodTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'service_name' => 'Service W manual',
            'division' => 'Dryer',
            'contract_number' => 'CONTRACT-A',
            'work_period_start' => '2026-04-27',
            'work_period_end' => '2026-05-03',
            'week_number' => 18,
            'invoice_date' => '2026-09-14',
        ], $overrides);
    }

    public function test_store_and_update_period_keep_submit_date_separate(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/api/invoices', $this->payload())
            ->assertCreated()->assertJsonPath('data.week_number', 18)
            ->assertJsonPath('data.work_period_start', '2026-04-27')
            ->assertJsonPath('data.contract_number', 'CONTRACT-A')
            ->assertJsonPath('data.invoice_date', '2026-09-14')->json('data.id');

        $this->putJson("/api/invoices/{$id}", $this->payload([
            'contract_number' => 'CONTRACT-B', 'week_number' => 9,
            'work_period_start' => '2026-03-01', 'work_period_end' => '2026-03-07',
        ]))->assertOk()->assertJsonPath('data.week_number', 9)
            ->assertJsonPath('data.contract_number', 'CONTRACT-B')
            ->assertJsonPath('data.work_period_start', '2026-03-01')
            ->assertJsonPath('data.invoice_date', '2026-09-14');
        $this->getJson("/api/invoices/{$id}")->assertOk()->assertJsonPath('data.week_number', 9);
    }

    public function test_contract_and_period_order_is_independent_of_insertion_description_and_submit_date(): void
    {
        $this->actingAsViewer();
        $june = Invoice::create($this->payload(['work_period_start' => '2026-06-01', 'work_period_end' => '2026-06-07', 'week_number' => 22]));
        $b = Invoice::create($this->payload(['contract_number' => 'CONTRACT-B', 'work_period_start' => '2025-01-01', 'work_period_end' => '2025-01-07', 'week_number' => 1]));
        $april = Invoice::create($this->payload(['service_name' => 'W99', 'invoice_date' => '2026-12-31']));
        $january = Invoice::create($this->payload(['work_period_start' => '2026-01-01', 'work_period_end' => '2026-01-07', 'week_number' => 1]));
        $lastYear = Invoice::create($this->payload(['work_period_start' => '2025-12-29', 'work_period_end' => '2026-01-04', 'week_number' => 53]));
        $legacy = Invoice::create(['service_name' => 'Legacy', 'division' => 'Dryer', 'service_date' => '2024-01-01']);
        $expected = [$lastYear->id, $january->id, $april->id, $june->id, $b->id, $legacy->id];
        $ids = [];
        foreach ([1, 2, 3] as $page) {
            $response = $this->getJson("/api/invoices?per_page=2&page={$page}")->assertOk();
            $ids = [...$ids, ...array_column($response->json('data'), 'id')];
        }
        $this->assertSame($expected, $ids);
        $this->assertSame(
            [$june->id, $april->id, $january->id, $lastYear->id, $b->id, $legacy->id],
            array_column($this->getJson('/api/invoices?sort=desc')->assertOk()->json('data'), 'id')
        );
    }

    public function test_weeks_are_numeric_and_break_ties_within_the_same_period(): void
    {
        $this->actingAsViewer();
        foreach ([22, 9, 18, 2] as $week) {
            Invoice::create($this->payload(['week_number' => $week]));
        }
        $this->assertSame([2, 9, 18, 22], array_column($this->getJson('/api/invoices')->assertOk()->json('data'), 'week_number'));
    }

    public function test_contract_filter_search_and_options_include_all_contracts_without_duplicates(): void
    {
        $this->actingAsViewer();
        Invoice::create($this->payload());
        Invoice::create($this->payload(['status' => 'paid']));
        Invoice::create($this->payload(['contract_number' => 'CONTRACT-B']));
        Invoice::create(['service_name' => 'Legacy', 'division' => 'Dryer']);
        $this->getJson('/api/invoices?contract_number=CONTRACT-A')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2)
            ->assertJsonPath('summary.by_status.paid', 1);
        $this->getJson('/api/invoices?search=CONTRACT-B')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/invoices/contracts')->assertOk()->assertExactJson(['data' => ['CONTRACT-A', 'CONTRACT-B']]);
    }

    public function test_legacy_invoice_can_be_read_and_edited_without_filling_new_fields(): void
    {
        $this->actingAsAdmin();
        $invoice = Invoice::create(['service_name' => 'Legacy W22', 'division' => 'Dryer', 'invoice_date' => '2026-08-14', 'notes' => 'Keep me']);
        $this->getJson("/api/invoices/{$invoice->id}")->assertOk()
            ->assertJsonPath('data.contract_number', null)->assertJsonPath('data.notes', 'Keep me');
        $this->putJson("/api/invoices/{$invoice->id}", ['service_name' => 'Legacy updated', 'division' => 'Dryer'])
            ->assertOk()->assertJsonPath('data.invoice_date', '2026-08-14')
            ->assertJsonPath('data.notes', 'Keep me')->assertJsonPath('data.work_period_start', null);
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_rejects_reversed_incomplete_periods_and_invalid_week_numbers(): void
    {
        $this->actingAsAdmin();
        foreach ([
            ['work_period_end' => '2026-01-01'],
            ['work_period_start' => null],
            ['work_period_end' => null],
            ['contract_number' => null],
            ['week_number' => 0], ['week_number' => 54], ['week_number' => 1.5], ['week_number' => 'W18'],
        ] as $invalid) {
            $this->postJson('/api/invoices', $this->payload($invalid))->assertUnprocessable();
        }
        $this->postJson('/api/invoices', $this->payload(['week_number' => null]))->assertCreated();
    }

    public function test_contract_options_require_authentication_and_office_cannot_write_periods(): void
    {
        $this->getJson('/api/invoices/contracts')->assertUnauthorized();
        $this->actingAsOffice();
        $this->postJson('/api/invoices', $this->payload())->assertForbidden();
    }
}
