<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class OfficeReadOnlyTest extends TestCase
{
    use ApiHelpers;
    use RefreshDatabase;

    public static function readEndpoints(): array
    {
        return [
            'suppliers' => ['/api/suppliers'],
            'employes' => ['/api/employes'],
            'items' => ['/api/items'],
            'transactions' => ['/api/transactions'],
            'stock history (barang masuk)' => ['/api/stock-history'],
            'groups' => ['/api/groups'],
            'trainings' => ['/api/trainings'],
            'invoices' => ['/api/invoices'],
            'mcus' => ['/api/mcus'],
            'contracts' => ['/api/contracts'],
            'activities' => ['/api/activities'],
        ];
    }

    /** @dataProvider readEndpoints */
    public function test_office_can_read(string $url): void
    {
        $this->actingAsOffice();
        $this->assertNotContains($this->getJson($url)->getStatusCode(), [401, 403]);
    }

    public static function writeEndpoints(): array
    {
        $w = [];
        foreach (['suppliers', 'items', 'transactions', 'transaction-items', 'activities', 'groups', 'trainings', 'employes', 'invoices', 'mcus', 'stock-history'] as $m) {
            $w["POST $m"] = ['post', "/api/$m"];
            $w["PUT $m"] = ['put', "/api/$m/1"];
            $w["DELETE $m"] = ['delete', "/api/$m/1"];
        }
        $w['POST stock-out (barang keluar)'] = ['post', '/api/stock-out'];
        $w['POST stock-in (barang masuk)'] = ['post', '/api/stock-history/in'];
        $w['POST participants'] = ['post', '/api/trainings/1/participants'];
        $w['POST participant update'] = ['post', '/api/participants/1'];
        $w['DELETE participant'] = ['delete', '/api/participants/1'];
        $w['POST resign'] = ['post', '/api/contracts/1/resign'];
        $w['POST renewal'] = ['post', '/api/contracts/1/renewals'];
        $w['PUT cpd'] = ['put', '/api/employes/1/cpd'];
        $w['POST invoice document'] = ['post', '/api/invoices/1/documents'];

        return $w;
    }

    /** @dataProvider writeEndpoints */
    public function test_office_cannot_write(string $method, string $url): void
    {
        // lewati route-model binding agar role middleware yang dievaluasi (bukan 404 id)
        $this->withoutMiddleware(SubstituteBindings::class);
        $this->actingAsOffice();
        $this->json($method, $url, [])->assertForbidden();
    }

    public function test_admin_is_not_forbidden_on_stock_in_and_out(): void
    {
        $this->actingAsAdmin();
        $this->assertNotContains($this->postJson('/api/stock-out', [])->getStatusCode(), [401, 403]);
        $this->assertNotContains($this->postJson('/api/stock-history/in', [])->getStatusCode(), [401, 403]);
        $this->assertNotContains($this->postJson('/api/suppliers', [])->getStatusCode(), [401, 403]);
    }
}
