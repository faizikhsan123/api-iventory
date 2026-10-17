<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class SupplierSpesialisAtkTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge(['name' => 'Toko ATK Jaya', 'pic' => 'Budi', 'spesialis' => 'atk', 'status' => 'active'], $over);
    }

    public function test_atk_supplier_can_be_created(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/suppliers', $this->payload())
            ->assertOk()->assertJsonPath('data.spesialis', 'atk');

        $this->assertDatabaseHas('suppliers', ['name' => 'Toko ATK Jaya', 'spesialis' => 'atk']);
    }

    public function test_supplier_can_be_updated_to_atk(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create(['spesialis' => 'apd']);

        $this->putJson("/api/suppliers/{$supplier->id}", $this->payload(['name' => 'Toko ATK Baru']))
            ->assertOk();

        $this->assertSame('atk', $supplier->fresh()->spesialis);
    }

    public function test_suppliers_can_be_filtered_by_atk(): void
    {
        $this->actingAsAdmin();
        Supplier::factory()->count(2)->create(['spesialis' => 'atk']);
        Supplier::factory()->create(['spesialis' => 'apd']);

        $this->getJson('/api/suppliers?spesialis=atk')
            ->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function test_invalid_spesialis_is_rejected(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();

        $this->postJson('/api/suppliers', $this->payload(['spesialis' => 'furniture']))
            ->assertUnprocessable()->assertJsonValidationErrors('spesialis');
        $this->putJson("/api/suppliers/{$supplier->id}", $this->payload(['spesialis' => 'furniture']))
            ->assertUnprocessable()->assertJsonValidationErrors('spesialis');
    }
}
