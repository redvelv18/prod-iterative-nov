<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjust_stock_increases_the_quantity_for_a_delivery(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $newQuantity = $product->adjustStock(5);

        $this->assertSame(15, $newQuantity);
        $this->assertSame(15, $product->quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 15,
        ]);
    }

    public function test_adjust_stock_decreases_the_quantity_for_a_sale(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $newQuantity = $product->adjustStock(-4);

        $this->assertSame(6, $newQuantity);
        $this->assertSame(6, $product->quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 6,
        ]);
    }

    public function test_adjust_stock_can_empty_the_remaining_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 3]);

        $product->adjustStock(-3);

        $this->assertSame(0, $product->fresh()->quantity);
    }

    public function test_adjust_stock_with_a_zero_delta_keeps_the_quantity_unchanged(): void
    {
        $product = Product::factory()->create(['quantity' => 7]);

        $newQuantity = $product->adjustStock(0);

        $this->assertSame(7, $newQuantity);
        $this->assertSame(7, $product->fresh()->quantity);
    }

    public function test_adjust_stock_rejects_removing_more_items_than_are_in_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 2]);

        try {
            $product->adjustStock(-3);
            $this->fail('An invalid adjustment should have been rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Cannot remove 3 items, only 2 are in stock.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(2, $product->fresh()->quantity);
    }

    public function test_adjust_stock_rejects_removing_items_when_the_stock_is_empty(): void
    {
        $product = Product::factory()->create(['quantity' => 0]);

        $this->expectException(InvalidArgumentException::class);

        $product->adjustStock(-1);
    }
}
