<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class EmployeeExitTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    public function test_resign_today_or_past_deactivates_immediately(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2020-01-01']);
        $date = now()->subDay()->toDateString();

        $this->postJson("/api/contracts/{$e->id}/resign", ['resign_date' => $date, 'reason' => 'Pindah kota'])
            ->assertOk()->assertJsonPath('data.status', 'inactive')->assertJsonPath('data.scheduled', false);

        $e->refresh();
        $this->assertSame('inactive', $e->status);
        $this->assertSame($date, $e->left_at->toDateString());
        $this->assertSame('resigned', $e->exit_type);
        $this->assertSame('Pindah kota', $e->resign_reason);
        $this->assertSame(1, Activity::count());
    }

    public function test_future_resign_is_scheduled_and_stays_active(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2020-01-01']);
        $date = now()->addDays(10)->toDateString();

        $this->postJson("/api/contracts/{$e->id}/resign", ['resign_date' => $date, 'reason' => 'x'])
            ->assertOk()->assertJsonPath('data.scheduled', true);

        $e->refresh();
        $this->assertSame('active', $e->status);
        $this->assertSame('resigned', $e->exit_type);

        $this->getJson("/api/contracts/{$e->id}")
            ->assertJsonPath('data.left_at', $date)->assertJsonPath('data.exit_type', 'resigned')
            ->assertJsonPath('data.resign_reason', 'x');
    }

    public function test_resign_validation_and_inactive_rejected(): void
    {
        $this->actingAsAdmin();
        $e = $this->makeEmployee(['contract_start' => '2026-01-10']);

        $this->postJson("/api/contracts/{$e->id}/resign", [])->assertStatus(422)->assertJsonValidationErrors(['resign_date', 'reason']);
        $this->postJson("/api/contracts/{$e->id}/resign", ['resign_date' => '2026-01-01', 'reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['resign_date']);

        $off = $this->makeEmployee(['status' => 'inactive']);
        $this->postJson("/api/contracts/{$off->id}/resign", ['resign_date' => '2026-02-01', 'reason' => 'x'])->assertStatus(422);
    }

    public function test_resign_requires_admin(): void
    {
        $this->actingAsViewer();
        $e = $this->makeEmployee();

        $this->postJson("/api/contracts/{$e->id}/resign", ['resign_date' => '2026-02-01', 'reason' => 'x'])->assertForbidden();
    }

    public function test_command_deactivates_due_resign_and_expired_contracts_idempotently(): void
    {
        $due = $this->makeEmployee(['exit_type' => 'resigned', 'left_at' => now()->subDay()->toDateString(), 'resign_reason' => 'x']);
        $scheduled = $this->makeEmployee(['exit_type' => 'resigned', 'left_at' => now()->addDays(5)->toDateString(), 'contract_end' => now()->subDays(3)->toDateString()]);
        $endDate = now()->subDays(20)->toDateString();
        $expired = $this->makeEmployee(['contract_end' => $endDate]);
        $running = $this->makeEmployee(['contract_end' => now()->addDays(5)->toDateString()]);
        $noEnd = $this->makeEmployee();

        $this->artisan('employes:deactivate-expired')->assertSuccessful();
        $this->artisan('employes:deactivate-expired')->assertSuccessful();

        $this->assertSame('inactive', $due->fresh()->status);
        $this->assertSame('active', $scheduled->fresh()->status);
        $this->assertSame('resigned', $scheduled->fresh()->exit_type);
        $x = $expired->fresh();
        $this->assertSame('inactive', $x->status);
        $this->assertSame($endDate, $x->left_at->toDateString());
        $this->assertSame('contract_ended', $x->exit_type);
        $this->assertSame('active', $running->fresh()->status);
        $this->assertSame('active', $noEnd->fresh()->status);
    }

    public function test_turnover_combines_selected_months(): void
    {
        $this->actingAsViewer();
        // headcount awal Januari = 4; keluar Jan (a), Mar (b), Feb (c, tidak dipilih)
        $this->makeEmployee(['contract_start' => '2025-01-01', 'status' => 'inactive', 'left_at' => '2026-01-15', 'exit_type' => 'resigned']);
        $this->makeEmployee(['contract_start' => '2025-01-01', 'status' => 'inactive', 'left_at' => '2026-03-10', 'exit_type' => 'contract_ended']);
        $this->makeEmployee(['contract_start' => '2025-01-01', 'status' => 'inactive', 'left_at' => '2026-02-10', 'exit_type' => 'resigned']);
        $this->makeEmployee(['contract_start' => '2025-01-01']);

        $d = $this->getJson('/api/dashboard/turnover?year=2026&months[]=1&months[]=3')->assertOk()->json('data');

        $this->assertSame('Jan, Mar 2026', $d['period']['label']);
        $this->assertSame(2, $d['summary']['left']);
        $this->assertSame(4, $d['summary']['headcount_start']);
        $this->assertSame(1, $d['summary']['headcount_end']);
        $this->assertEquals(2.5, $d['summary']['average_headcount']);
        $this->assertEquals(80.0, $d['summary']['turnover_rate']);
        $this->assertCount(12, $d['monthly']);
        $this->assertCount(2, $d['left_employees']);

        $this->assertSame(1, $this->getJson('/api/dashboard/turnover?year=2026&month=2')->json('data.summary.left'));
        $this->assertSame(3, $this->getJson('/api/dashboard/turnover?year=2026')->json('data.summary.left'));
        $this->getJson('/api/dashboard/turnover?months[]=13')->assertStatus(422);
    }
}
