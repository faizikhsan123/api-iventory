<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class ItemCategoryAtkTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge(['name' => 'Pulpen', 'category' => 'atk', 'unit' => 'pcs'], $over);
    }

    public function test_atk_item_can_be_created(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/items', $this->payload())
            ->assertCreated()->assertJsonPath('data.category', 'atk');

        $this->assertDatabaseHas('items', ['name' => 'Pulpen', 'category' => 'atk']);
    }

    public function test_item_can_be_updated_to_atk(): void
    {
        $this->actingAsAdmin();
        $item = Item::factory()->create(['category' => 'apd']);

        $this->putJson("/api/items/{$item->id}", $this->payload(['name' => $item->name]))
            ->assertOk();

        $this->assertSame('atk', $item->fresh()->category);
    }

    public function test_items_can_be_filtered_by_atk(): void
    {
        $this->actingAsAdmin();
        Item::factory()->count(2)->create(['category' => 'atk']);
        Item::factory()->create(['category' => 'apd']);

        $this->getJson('/api/items?category=atk')
            ->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function test_invalid_category_is_rejected(): void
    {
        $this->actingAsAdmin();
        $item = Item::factory()->create();

        $this->postJson('/api/items', $this->payload(['category' => 'furniture']))
            ->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->putJson("/api/items/{$item->id}", $this->payload(['category' => 'furniture']))
            ->assertUnprocessable()->assertJsonValidationErrors('category');
    }
}
