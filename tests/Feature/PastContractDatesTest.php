<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

// Rekap data kontrak lama: tanggal lampau (2024/2025) harus diterima.
class PastContractDatesTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'id_number' => 'EMP70001',
            'name' => 'Budi Santoso',
            'division' => 'Dryer',
            'position' => 'Technician',
            'contract_start' => '2024-01-01',
            'contract_end' => '2024-12-31',
        ], $over);
    }

    public function test_employee_with_2024_and_2025_contract_can_be_created(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/employes', $this->payload())->assertCreated();
        $this->postJson('/api/employes', $this->payload([
            'id_number' => 'EMP70002', 'contract_start' => '2025-03-01', 'contract_end' => '2025-09-01',
        ]))->assertCreated();

        $this->assertDatabaseHas('employes', ['id_number' => 'EMP70001']);
        $this->assertDatabaseHas('employes', ['id_number' => 'EMP70002']);
    }

    public function test_create_rejects_contract_end_before_start(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/employes', $this->payload(['contract_start' => '2025-06-01', 'contract_end' => '2025-05-31']))
            ->assertStatus(422)->assertJsonValidationErrors(['contract_end']);
    }

    public function test_employee_can_be_updated_with_past_contract_dates(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2026-01-01', 'contract_end' => '2026-12-31']);

        $body = [
            'id_number' => $e->id_number,
            'name' => 'Budi Santoso',
            'division' => 'Dryer',
            'position' => 'Technician',
            'status' => 'active',
            'contract_start' => '2024-02-01',
            'contract_end' => '2025-01-31',
        ];

        $this->putJson("/api/employes/{$e->id}", $body)->assertOk();
        $e->refresh();
        $this->assertSame('2024-02-01', $e->contract_start->toDateString());
        $this->assertSame('2025-01-31', $e->contract_end->toDateString());

        $this->putJson("/api/employes/{$e->id}", [...$body, 'contract_end' => '2024-01-31'])
            ->assertStatus(422)->assertJsonValidationErrors(['contract_end']);
    }

    public function test_past_renewal_is_history_only_and_does_not_move_active_contract_end(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2024-01-01', 'contract_end' => '2027-12-31']);

        $this->postJson("/api/contracts/{$e->id}/renewals", ['new_end' => '2025-12-31', 'previous_end' => '2024-12-31'])->assertCreated();
        $this->postJson("/api/contracts/{$e->id}/renewals", ['new_end' => '2024-12-31'])->assertCreated();

        $this->assertSame('2027-12-31', $e->fresh()->contract_end->toDateString());
        $this->assertDatabaseHas('contract_renewals', ['employes_id' => $e->id, 'new_end' => '2025-12-31', 'previous_end' => '2024-12-31']);
        $this->assertDatabaseHas('contract_renewals', ['employes_id' => $e->id, 'new_end' => '2024-12-31', 'previous_end' => null]);
    }

    public function test_later_renewal_still_updates_contract_end_and_delete_restores_it(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2024-01-01', 'contract_end' => '2024-12-31']);

        $this->postJson("/api/contracts/{$e->id}/renewals", ['new_end' => '2025-12-31'])->assertCreated();
        $this->assertSame('2025-12-31', $e->fresh()->contract_end->toDateString());

        $id = $e->contractRenewals()->first()->id;
        $this->assertSame('2024-12-31', $e->contractRenewals()->first()->previous_end->toDateString());
        $this->deleteJson("/api/contract-renewals/{$id}")->assertOk();
        $this->assertSame('2024-12-31', $e->fresh()->contract_end->toDateString());
    }

    public function test_deleting_history_renewal_keeps_contract_end(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2024-01-01', 'contract_end' => '2027-12-31']);

        $this->postJson("/api/contracts/{$e->id}/renewals", ['new_end' => '2025-12-31'])->assertCreated();
        $id = $e->contractRenewals()->first()->id;
        $this->deleteJson("/api/contract-renewals/{$id}")->assertOk();

        $this->assertSame('2027-12-31', $e->fresh()->contract_end->toDateString());
        $this->assertDatabaseCount('contract_renewals', 0);
    }

    public function test_renewal_before_contract_start_is_rejected(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2024-06-01', 'contract_end' => '2025-06-01']);

        $this->postJson("/api/contracts/{$e->id}/renewals", ['new_end' => '2024-05-31'])
            ->assertStatus(422)->assertJsonValidationErrors(['new_end']);
        $this->postJson("/api/contracts/{$e->id}/renewals", [])->assertStatus(422)->assertJsonValidationErrors(['new_end']);
    }

    public function test_resign_in_the_past_is_accepted_but_not_before_contract_start(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2024-01-01', 'contract_end' => '2025-12-31']);

        $this->postJson("/api/contracts/{$e->id}/resign", ['resign_date' => '2023-12-31', 'reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['resign_date']);
        $this->postJson("/api/contracts/{$e->id}/resign", ['resign_date' => '2025-06-15', 'reason' => 'Pindah'])
            ->assertOk()->assertJsonPath('data.scheduled', false);
    }
}
