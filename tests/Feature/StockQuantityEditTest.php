<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ApiHelpers;
use Tests\TestCase;

class StockQuantityEditTest extends TestCase
{
    use ApiHelpers, RefreshDatabase;

    private function item(int $stock = 10): Item
    {
        return Item::factory()->create(['current_stock' => $stock, 'price' => 1000, 'avg_price' => 1000, 'unit' => 'pcs']);
    }

    /** Meniru persis yang ditulis StockOutController::store (endpoint aslinya punya masalah autoload FormRequest di luar classmap). */
    private function stockOut(Item $item, int $qty = 3): TransactionItem
    {
        $trx = Transaction::create([
            'transaction_number' => 'TRX-'.strtoupper(uniqid()),
            'date' => '2026-10-20',
            'employes_id' => $this->makeEmployee()->id,
        ]);
        $ti = TransactionItem::create(['transactions_id' => $trx->id, 'items_id' => $item->id, 'qty' => $qty]);
        $item->decrement('current_stock', $qty);
        StockHistory::create([
            'item_id' => $item->id, 'qty' => $qty, 'type' => 'out', 'user_id' => auth()->id() ?? User::factory()->create()->id,
            'date' => '2026-10-20',
        ]);

        return $ti;
    }

    private function stockIn(Item $item, int $qty = 5): StockHistory
    {
        $sup = Supplier::factory()->create();
        $this->postJson('/api/stock-history/in', [
            'supplier_id' => $sup->id,
            'date' => '2026-10-20',
            'items' => [['item_id' => $item->id, 'qty' => $qty, 'unit' => 'pcs']],
        ])->assertOk();

        return StockHistory::where('item_id', $item->id)->where('type', 'in')->firstOrFail();
    }

    private function outSum(Item $item): int
    {
        return (int) StockHistory::where('item_id', $item->id)->where('type', 'out')->sum('qty');
    }

    public function test_stock_out_increase_3_to_4_reduces_stock_by_one(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $ti = $this->stockOut($item, 3); // stok 7

        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.old_qty', 3)
            ->assertJsonPath('data.new_qty', 4)
            ->assertJsonPath('data.delta', 1)
            ->assertJsonPath('data.current_stock', 6);

        $this->assertSame(6, (int) $item->fresh()->current_stock);
        $this->assertSame(4, (int) $ti->fresh()->qty);
        // ledger ikut: baris history out di-update, bukan digandakan
        $this->assertSame(4, $this->outSum($item));
        $this->assertSame(1, StockHistory::where('item_id', $item->id)->where('type', 'out')->count());
        $log = Activity::where('activity', 'Mengubah Qty Barang Keluar')->get();
        $this->assertCount(1, $log);
        $this->assertStringContainsString('3 menjadi 4', $log->first()->detail);
    }

    public function test_stock_out_decrease_3_to_2_adds_stock_back(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $ti = $this->stockOut($item, 3);

        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => 2])
            ->assertOk()->assertJsonPath('data.current_stock', 8);

        $this->assertSame(8, (int) $item->fresh()->current_stock);
        $this->assertSame(2, $this->outSum($item));
    }

    public function test_stock_out_without_matching_ledger_row_writes_signed_correction(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $ti = $this->stockOut($item, 3);
        StockHistory::where('item_id', $item->id)->where('type', 'out')->delete();

        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => 5])->assertOk();

        $this->assertSame(2, $this->outSum($item));
        $this->assertSame(5, (int) $item->fresh()->current_stock);
    }

    public function test_stock_out_same_qty_is_noop(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $ti = $this->stockOut($item, 3);
        $before = Activity::count();

        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => 3])
            ->assertOk()->assertJsonPath('data.delta', 0);

        $this->assertSame(7, (int) $item->fresh()->current_stock);
        $this->assertSame($before, Activity::count());
    }

    public function test_stock_out_validation_rejects_zero_negative_and_non_integer(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $ti = $this->stockOut($item, 3);

        foreach ([0, -1, 'abc', 1.5, null] as $bad) {
            $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors('quantity');
        }
        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", [])->assertStatus(422);

        $this->assertSame(7, (int) $item->fresh()->current_stock);
        $this->assertSame(3, (int) $ti->fresh()->qty);
    }

    public function test_stock_out_exceeding_available_stock_is_rejected_atomically(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(5);
        $ti = $this->stockOut($item, 3); // stok 2

        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => 6]) // butuh +3, tersedia 2
            ->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(2, (int) $item->fresh()->current_stock);
        $this->assertSame(3, (int) $ti->fresh()->qty);
        $this->assertSame(3, $this->outSum($item));
        $this->assertSame(0, Activity::where('activity', 'Mengubah Qty Barang Keluar')->count());

        // tepat habis (stok jadi 0) diperbolehkan
        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => 5])->assertOk();
        $this->assertSame(0, (int) $item->fresh()->current_stock);
    }

    public function test_stock_out_unknown_id_is_404(): void
    {
        $this->actingAsAdmin();
        $this->patchJson('/api/transaction-items/999999/quantity', ['quantity' => 2])->assertNotFound();
    }

    public function test_stock_in_increase_and_decrease(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $sh = $this->stockIn($item, 5); // stok 15

        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 8])
            ->assertOk()->assertJsonPath('data.delta', 3)->assertJsonPath('data.current_stock', 18);
        $this->assertSame(8, (int) $sh->fresh()->qty);

        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 4])
            ->assertOk()->assertJsonPath('data.current_stock', 14);
        $this->assertSame(14, (int) $item->fresh()->current_stock);
        $this->assertSame(2, Activity::where('activity', 'Mengubah Qty Barang Masuk')->count());
    }

    public function test_stock_in_decrease_below_zero_stock_is_rejected(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(0);
        $sh = $this->stockIn($item, 5);        // stok 5
        $item->update(['current_stock' => 2]); // sebagian sudah keluar

        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 1]) // -4 -> -2
            ->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(2, (int) $item->fresh()->current_stock);
        $this->assertSame(5, (int) $sh->fresh()->qty);

        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 3])->assertOk(); // -2 -> 0
        $this->assertSame(0, (int) $item->fresh()->current_stock);
    }

    public function test_stock_in_same_qty_noop_validation_and_price_untouched(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $sh = $this->stockIn($item, 5);
        $avg = $item->fresh()->avg_price;

        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 5])->assertOk()->assertJsonPath('data.delta', 0);
        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 0])->assertStatus(422);
        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 9])->assertOk();

        $this->assertEquals($avg, $item->fresh()->avg_price);
        $this->assertEquals(1000, $item->fresh()->price);
    }

    public function test_stock_in_endpoint_rejects_out_rows(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $this->stockOut($item, 3);
        $out = StockHistory::where('item_id', $item->id)->where('type', 'out')->firstOrFail();

        $this->patchJson("/api/stock-history/{$out->id}/quantity", ['quantity' => 1])->assertNotFound();
        $this->assertSame(7, (int) $item->fresh()->current_stock);
    }

    public function test_office_is_forbidden(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $ti = $this->stockOut($item, 3);
        $sh = $this->stockIn($item, 5); // stok 12

        $this->actingAsOffice();
        $this->patchJson("/api/transaction-items/{$ti->id}/quantity", ['quantity' => 4])->assertForbidden();
        $this->patchJson("/api/stock-history/{$sh->id}/quantity", ['quantity' => 9])->assertForbidden();
        $this->assertSame(12, (int) $item->fresh()->current_stock);
        $this->assertSame(3, (int) $ti->fresh()->qty);
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->patchJson('/api/transaction-items/1/quantity', ['quantity' => 2])->assertUnauthorized();
        $this->patchJson('/api/stock-history/1/quantity', ['quantity' => 2])->assertUnauthorized();
    }

    public function test_report_endpoints_expose_editable_row_ids(): void
    {
        $this->actingAsAdmin();
        $item = $this->item(10);
        $ti = $this->stockOut($item, 3);
        $sh = $this->stockIn($item, 5);

        $this->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.items.0.id', $ti->id)
            ->assertJsonPath('data.0.items.0.qty', 3);

        $this->getJson('/api/stock-history')
            ->assertOk()
            ->assertJsonFragment(['id' => $sh->id, 'type' => 'in']);
    }
}
