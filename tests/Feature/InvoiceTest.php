<?php

namespace Tests\Feature;

use App\Enums\Division;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'service_name' => 'Kalibrasi Gas Analyzer',
            'service_date' => '2026-10-01',
            'service_description' => 'Detail jasa',
            'division' => 'Dryer',
            'client' => 'PT A',
            'amount' => 1000,
            'invoice_date' => '2026-10-05',
        ], $over);
    }

    public function test_constants_match_agreed_values(): void
    {
        $this->assertSame(['drafting_timesheet', 'waiting_approved_timesheet', 'waiting_service_receipt', 'waiting_work_order', 'paid'], Invoice::STATUSES);
        $this->assertSame(Division::values(), Invoice::DIVISIONS);
    }

    public function test_store_defaults_to_drafting_timesheet_and_maps_service_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/invoices', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'drafting_timesheet')
            ->assertJsonPath('data.service_name', 'Kalibrasi Gas Analyzer')
            ->assertJsonPath('data.service_date', '2026-10-01')
            ->assertJsonPath('data.service_description', 'Detail jasa');

        $this->assertDatabaseHas('invoice_status_logs', ['status' => 'drafting_timesheet']);
    }

    public function test_validation_rejects_old_title_old_status_and_old_division(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/invoices', $this->payload(['service_name' => null]))->assertStatus(422)->assertJsonValidationErrors('service_name');
        $this->postJson('/api/invoices', $this->payload(['status' => 'draft']))->assertStatus(422)->assertJsonValidationErrors('status');
        $this->postJson('/api/invoices', $this->payload(['division' => 'PMR']))->assertStatus(422)->assertJsonValidationErrors('division');
    }

    public function test_non_admin_cannot_write(): void
    {
        $this->actingAsViewer();

        $this->postJson('/api/invoices', $this->payload())->assertForbidden();
    }

    public function test_update_status_walks_stages(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/api/invoices', $this->payload())->json('data.id');

        $this->postJson("/api/invoices/{$id}/status", ['status' => 'waiting_work_order', 'status_date' => '2026-10-09'])
            ->assertOk()
            ->assertJsonPath('data.status', 'waiting_work_order');

        $this->postJson("/api/invoices/{$id}/status", ['status' => 'bogus', 'status_date' => '2026-10-09'])->assertStatus(422);
    }

    public function test_sort_status_orders_by_stage_and_ignores_invalid_value(): void
    {
        $this->actingAsViewer();
        foreach (['paid', 'drafting_timesheet', 'waiting_work_order', 'waiting_approved_timesheet', 'waiting_service_receipt'] as $s) {
            Invoice::create($this->payload(['status' => $s]));
        }

        $asc = collect($this->getJson('/api/invoices?sort_status=asc')->json('data'))->pluck('status')->all();
        $this->assertSame(Invoice::STATUSES, $asc);

        $desc = collect($this->getJson('/api/invoices?sort_status=desc')->json('data'))->pluck('status')->all();
        $this->assertSame(array_reverse(Invoice::STATUSES), $desc);

        // nilai tidak valid diabaikan: urut id terbaru (paid dibuat pertama jadi terakhir)
        $this->getJson('/api/invoices?sort_status=DROP')->assertOk()->assertJsonPath('data.4.status', 'paid');
    }

    public function test_search_by_service_name_and_summary_follow_constants(): void
    {
        $this->actingAsViewer();
        Invoice::create($this->payload(['service_name' => 'Unik Banget', 'status' => 'paid']));
        Invoice::create($this->payload(['service_name' => 'Lain', 'division' => 'Safety']));

        $res = $this->getJson('/api/invoices?search=Unik')->assertOk();
        $res->assertJsonCount(1, 'data');
        $this->assertSame(Invoice::STATUSES, array_keys($res->json('summary.by_status')));
        $this->assertSame(Invoice::DIVISIONS, array_keys($res->json('summary.by_division')));
        $this->assertSame(1, $res->json('summary.by_division.Safety'));
    }
}
