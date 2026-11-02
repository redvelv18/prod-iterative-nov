<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_stock_increases_the_quantity_for_a_delivery(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $newQuantity = $product->addStock(5);

        $this->assertSame(15, $newQuantity);
        $this->assertSame(15, $product->quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 15,
        ]);
    }

    public function test_add_stock_accumulates_successive_deliveries(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $product->addStock(5);
        $product->addStock(2);

        $this->assertSame(17, $product->fresh()->quantity);
    }

    public function test_add_stock_rejects_an_amount_of_zero(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        try {
            $product->addStock(0);
            $this->fail('An empty delivery should have been rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'A stock adjustment must cover at least one item.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(10, $product->fresh()->quantity);
    }

    public function test_add_stock_rejects_a_negative_amount(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $this->expectException(InvalidArgumentException::class);

        $product->addStock(-3);
    }

    public function test_remove_stock_decreases_the_quantity_for_a_sale(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $newQuantity = $product->removeStock(4);

        $this->assertSame(6, $newQuantity);
        $this->assertSame(6, $product->quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 6,
        ]);
    }

    public function test_remove_stock_can_empty_the_remaining_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 3]);

        $product->removeStock(3);

        $this->assertSame(0, $product->fresh()->quantity);
    }

    public function test_remove_stock_rejects_removing_more_items_than_are_in_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 2]);

        try {
            $product->removeStock(3);
            $this->fail('An impossible sale should have been rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Cannot remove 3 items, only 2 are in stock.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(2, $product->fresh()->quantity);
    }

    public function test_remove_stock_rejects_a_sale_when_the_stock_is_empty(): void
    {
        $product = Product::factory()->create(['quantity' => 0]);

        $this->expectException(InvalidArgumentException::class);

        $product->removeStock(1);
    }

    public function test_remove_stock_rejects_a_negative_amount(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $this->expectException(InvalidArgumentException::class);

        $product->removeStock(-2);
    }

    public function test_add_tag_attaches_a_new_tag_to_the_product(): void
    {
        $product = Product::factory()->create();

        $tag = $product->addTag('Organic');

        $this->assertSame('Organic', $tag->name);
        $this->assertDatabaseHas('tags', ['name' => 'Organic']);
        $this->assertDatabaseHas('product_tag', [
            'product_id' => $product->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_add_tag_reuses_a_tag_already_used_by_another_product(): void
    {
        $first = Product::factory()->create();
        $second = Product::factory()->create();

        $tag = $first->addTag('Gift');
        $second->addTag('GIFT');

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseCount('product_tag', 2);
        $this->assertTrue($second->tags()->whereKey($tag->id)->exists());
    }

    public function test_add_tag_twice_does_not_duplicate_the_link(): void
    {
        $product = Product::factory()->create();

        $product->addTag('Seasonal');
        $product->addTag('seasonal');

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseCount('product_tag', 1);
        $this->assertCount(1, $product->fresh()->tags);
    }

    public function test_remove_tag_detaches_the_link_but_keeps_the_tag(): void
    {
        $product = Product::factory()->create();
        $tag = $product->addTag('Sale');

        $product->removeTag($tag);

        $this->assertDatabaseMissing('product_tag', [
            'product_id' => $product->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Sale']);
        $this->assertCount(0, $product->fresh()->tags);
    }
}
