<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * The full journey through the application: create a product, view it,
     * edit it, then receive a delivery and sell some of the stock.
     */
    public function test_a_product_can_be_created_edited_and_have_its_stock_adjusted(): void
    {
        $create = $this->post(route('products.store'), [
            'name' => 'Basmati Rice 1 kg',
            'description' => 'Fragrant long-grain rice.',
            'expiration_date' => today()->addYear()->toDateString(),
            'status' => ProductStatus::Active->value,
            'quantity' => 12,
        ]);

        $product = Product::query()->sole();

        $create->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Product created.');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Basmati Rice 1 kg')
            ->assertSee('Fragrant long-grain rice.')
            ->assertSee('12 pcs')
            ->assertSee('Add stock')
            ->assertSee('Remove stock');

        $update = $this->put(route('products.update', $product), [
            'name' => 'Basmati Rice 2 kg',
            'description' => 'Fragrant long-grain rice, family bag.',
            'expiration_date' => today()->addMonths(6)->toDateString(),
            'status' => ProductStatus::Inactive->value,
            'quantity' => 20,
        ]);

        $update->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Product updated.');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Basmati Rice 2 kg')
            ->assertSee('Fragrant long-grain rice, family bag.')
            ->assertSee('20 pcs');

        $delivery = $this->patch(route('products.quantity', $product), [
            'direction' => 'increase',
            'amount' => 5,
        ]);

        $delivery->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Stock increased by 5. New quantity: 25 pcs.');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('25 pcs');

        $sale = $this->patch(route('products.quantity', $product), [
            'direction' => 'decrease',
            'amount' => 10,
        ]);

        $sale->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Stock decreased by 10. New quantity: 15 pcs.');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('15 pcs');

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Basmati Rice 2 kg');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Basmati Rice 2 kg',
            'description' => 'Fragrant long-grain rice, family bag.',
            'status' => ProductStatus::Inactive->value,
            'quantity' => 15,
        ]);
    }

    public function test_a_sale_beyond_the_available_stock_is_rejected_and_keeps_the_stock_intact(): void
    {
        $product = Product::factory()->create([
            'name' => 'Greek Yogurt 500 ml',
            'quantity' => 3,
        ]);

        $response = $this
            ->from(route('products.show', $product))
            ->followingRedirects()
            ->patch(route('products.quantity', $product), [
                'direction' => 'decrease',
                'amount' => 4,
            ]);

        $response->assertOk()
            ->assertSee('Only 3 items are in stock, so 4 cannot be removed.')
            ->assertSee('3 pcs');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_an_invalid_stock_adjustment_from_the_product_page_keeps_the_previous_quantity(): void
    {
        $product = Product::factory()->create([
            'name' => 'Dark Chocolate Bar 100 g',
            'quantity' => 8,
        ]);

        $response = $this
            ->from(route('products.show', $product))
            ->followingRedirects()
            ->patch(route('products.quantity', $product), [
                'direction' => 'increase',
                'amount' => 0,
            ]);

        $response->assertOk()
            ->assertSee('The amount field must be at least 1.')
            ->assertSee('8 pcs');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 8,
        ]);
    }
}
