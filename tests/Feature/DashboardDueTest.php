<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class DashboardDueTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    public function test_threshold_is_30_days(): void
    {
        $this->assertSame(30, DashboardController::DUE_DAYS);
    }

    public function test_contract_and_mcu_windows_use_30_days_and_keep_overdue(): void
    {
        $this->actingAsViewer();

        $in = fn (int $d) => now()->addDays($d)->toDateString();

        $overdue = $this->makeEmployee(['contract_end' => $in(-5)]);
        $d25 = $this->makeEmployee(['contract_end' => $in(25)]);
        $d30 = $this->makeEmployee(['contract_end' => $in(30)]);
        $d31 = $this->makeEmployee(['contract_end' => $in(31)]);
        $inactive = $this->makeEmployee(['contract_end' => $in(10), 'status' => 'inactive']);

        $mcuOverdue = $overdue->mcus()->create(['place_name' => 'RS', 'mcu_date' => $in(-300), 'next_mcu_date' => $in(-2)]);
        $mcu25 = $d25->mcus()->create(['place_name' => 'RS', 'mcu_date' => $in(-300), 'next_mcu_date' => $in(25)]);
        $mcu40 = $d31->mcus()->create(['place_name' => 'RS', 'mcu_date' => $in(-300), 'next_mcu_date' => $in(40)]);

        $data = $this->getJson('/api/dashboard/summary')->assertOk()->json('data');

        $contractIds = collect($data['kontrak_berakhir'])->pluck('id')->all();
        $this->assertContains($overdue->id, $contractIds);
        $this->assertContains($d25->id, $contractIds);
        $this->assertContains($d30->id, $contractIds);
        $this->assertNotContains($d31->id, $contractIds);
        $this->assertNotContains($inactive->id, $contractIds);

        $mcuIds = collect($data['mcu_berikutnya'])->pluck('id')->all();
        $this->assertContains($mcuOverdue->id, $mcuIds);
        $this->assertContains($mcu25->id, $mcuIds);
        $this->assertNotContains($mcu40->id, $mcuIds);
    }
}
