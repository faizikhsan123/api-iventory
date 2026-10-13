<?php

namespace Tests\Feature;

use App\Exports\RfqExport;
use App\Models\Rfq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class RfqExportTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function rfq(array $over = []): Rfq
    {
        return Rfq::create(array_merge([
            'enquiry_no' => 'RFQ'.fake()->unique()->numerify('########-###'),
            'rfq_date' => '2026-10-01',
            'type' => 'Spare',
            'opportunity_name' => 'Opp',
            'customer' => 'PT A',
            'status' => 'pending',
            'priority_code' => 'A1',
        ], $over));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/rfqs/export')->assertUnauthorized();
    }

    public function test_any_logged_in_user_downloads_filtered_and_sorted_export(): void
    {
        $this->actingAsViewer();
        $this->rfq(['enquiry_no' => 'OLD', 'rfq_date' => '2026-09-01']);
        $this->rfq(['enquiry_no' => 'NEW', 'rfq_date' => '2026-10-05']);
        $this->rfq(['enquiry_no' => 'WON', 'status' => 'won', 'priority_code' => 'B1']);

        Excel::fake();

        $this->get('/api/rfqs/export?status=pending&priority=A')->assertOk();
        Excel::matchByRegex();
        Excel::assertDownloaded('/^log-rfq-.*\.xlsx$/', function (RfqExport $export) {
            $this->assertSame(['NEW', 'OLD'], $export->collection()->pluck('enquiry_no')->all());
            $this->assertCount(count($export->headings()), $export->map($export->collection()->first()));

            return true;
        });
    }

    public function test_real_export_returns_xlsx(): void
    {
        $this->actingAsViewer();
        $this->rfq();

        $res = $this->get('/api/rfqs/export')->assertOk();
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('content-type'));
    }
}
