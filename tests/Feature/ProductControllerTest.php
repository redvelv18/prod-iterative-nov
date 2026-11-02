<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_shows_existing_products(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.index'));

        $response->assertOk()
            ->assertSee($product->name);
    }

    public function test_create_shows_the_product_form(): void
    {
        $response = $this->get(route('products.create'));

        $response->assertOk()
            ->assertSee('Name')
            ->assertSee('Description')
            ->assertSee('Expiration date')
            ->assertSee('Status')
            ->assertSee('Quantity');
    }

    public function test_valid_payload_stores_a_product_and_redirects_to_its_page(): void
    {
        $product = Product::factory()->make();

        $response = $this->post(route('products.store'), static::payload($product));

        $stored = Product::query()->sole();

        $response->assertRedirect(route('products.show', $stored))
            ->assertSessionHas('status', 'Product created.');

        $this->assertModelExists($stored);
        $this->assertSame($product->name, $stored->name);
        $this->assertSame($product->description, $stored->description);
        $this->assertSame($product->expiration_date->toDateString(), $stored->expiration_date->toDateString());
        $this->assertSame($product->status, $stored->status);
        $this->assertSame($product->quantity, $stored->quantity);
    }

    public function test_empty_payload_rejects_with_errors_and_stores_nothing(): void
    {
        $response = $this->post(route('products.store'), []);

        $response->assertRedirect()
            ->assertSessionHasErrors(['name', 'quantity', 'status'])
            ->assertSessionHasErrorsIn('default', ['name' => 'The name field is required.']);

        $this->assertDatabaseCount('products', 0);
    }

    public static function invalidPayloads(): array
    {
        return [
            'negative quantity' => [['quantity' => -1]],
            'non-numeric quantity' => [['quantity' => 'many']],
            'invalid status' => [['status' => 'recalled']],
            'past expiration date' => [['expiration_date' => '2020-01-01']],
            'name beyond length limit' => [['name' => str_repeat('a', 256)]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_payload_rejects_and_stores_nothing(array $override): void
    {
        $payload = static::payload(Product::factory()->make(), $override);

        $response = $this->post(route('products.store'), $payload);

        $response->assertRedirect()
            ->assertSessionHasErrors(array_keys($override));

        $this->assertDatabaseCount('products', 0);
    }

    public function test_show_shows_the_stored_product_details(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee($product->name)
            ->assertSee($product->description)
            ->assertSee($product->status->label())
            ->assertSee((string) $product->quantity);
    }

    public function test_show_offers_controls_to_adjust_the_stock_level(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee(route('products.quantity', $product), false)
            ->assertSee('Add stock')
            ->assertSee('Remove stock');
    }

    public function test_adjust_quantity_increases_the_stock_and_redirects_back(): void
    {
        $product = Product::factory()->create(['quantity' => 4]);

        $response = $this->patch(route('products.quantity', $product), [
            'direction' => 'increase',
            'amount' => 6,
        ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Stock increased by 6. New quantity: 10 pcs.');

        $this->assertSame(10, $product->fresh()->quantity);
    }

    public function test_adjust_quantity_decreases_the_stock_and_redirects_back(): void
    {
        $product = Product::factory()->create(['quantity' => 9]);

        $response = $this->patch(route('products.quantity', $product), [
            'direction' => 'decrease',
            'amount' => 4,
        ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Stock decreased by 4. New quantity: 5 pcs.');

        $this->assertSame(5, $product->fresh()->quantity);
    }

    public function test_adjust_quantity_rejects_removing_more_items_than_are_in_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 3]);

        $response = $this->patch(route('products.quantity', $product), [
            'direction' => 'decrease',
            'amount' => 4,
        ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors([
                'amount' => 'Only 3 items are in stock, so 4 cannot be removed.',
            ]);

        $this->assertSame(3, $product->fresh()->quantity);
    }

    public static function invalidAdjustmentPayloads(): array
    {
        return [
            'missing direction' => [['amount' => 1], 'direction'],
            'unknown direction' => [['direction' => 'sideways', 'amount' => 1], 'direction'],
            'zero amount' => [['direction' => 'increase', 'amount' => 0], 'amount'],
            'negative amount' => [['direction' => 'decrease', 'amount' => -2], 'amount'],
            'non-numeric amount' => [['direction' => 'increase', 'amount' => 'many'], 'amount'],
            'amount above the limit' => [['direction' => 'increase', 'amount' => 1001], 'amount'],
        ];
    }

    #[DataProvider('invalidAdjustmentPayloads')]
    public function test_adjust_quantity_rejects_invalid_payloads(array $payload, string $field): void
    {
        $product = Product::factory()->create(['quantity' => 5]);

        $response = $this
            ->from(route('products.show', $product))
            ->patch(route('products.quantity', $product), $payload);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors($field);

        $this->assertSame(5, $product->fresh()->quantity);
    }

    public function test_show_exposes_the_hooks_the_quantity_script_binds_to(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('data-quantity-form', false)
            ->assertSee('data-quantity', false)
            ->assertSee('data-quantity-feedback', false);
    }

    public function test_ajax_increase_returns_json_with_the_new_quantity(): void
    {
        $product = Product::factory()->create(['quantity' => 4]);

        $response = $this->patchJson(route('products.quantity', $product), [
            'direction' => 'increase',
            'amount' => 6,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Stock increased by 6. New quantity: 10 pcs.',
                'quantity' => 10,
            ]);

        $this->assertSame(10, $product->fresh()->quantity);
    }

    public function test_ajax_decrease_returns_json_with_the_new_quantity(): void
    {
        $product = Product::factory()->create(['quantity' => 9]);

        $response = $this->patchJson(route('products.quantity', $product), [
            'direction' => 'decrease',
            'amount' => 4,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Stock decreased by 4. New quantity: 5 pcs.',
                'quantity' => 5,
            ]);

        $this->assertSame(5, $product->fresh()->quantity);
    }

    public function test_ajax_over_removal_returns_a_json_error_and_keeps_the_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 3]);

        $response = $this->patchJson(route('products.quantity', $product), [
            'direction' => 'decrease',
            'amount' => 4,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Only 3 items are in stock, so 4 cannot be removed.',
                'errors' => [
                    'amount' => ['Only 3 items are in stock, so 4 cannot be removed.'],
                ],
            ]);

        $this->assertSame(3, $product->fresh()->quantity);
    }

    public function test_ajax_invalid_payload_returns_validation_errors_as_json(): void
    {
        $product = Product::factory()->create(['quantity' => 5]);

        $response = $this->patchJson(route('products.quantity', $product), [
            'direction' => 'increase',
            'amount' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount' => 'The amount field must be at least 1.']);

        $this->assertSame(5, $product->fresh()->quantity);
    }

    public function test_edit_shows_the_form_filled_with_current_values(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.edit', $product));

        $response->assertOk()
            ->assertSee($product->name);
    }

    public function test_valid_payload_updates_the_product_and_redirects_back(): void
    {
        $product = Product::factory()->create();

        $response = $this->put(route('products.update', $product), [
            'name' => 'Sunflower Oil 750 ml',
            'description' => 'Cold-pressed for salads.',
            'expiration_date' => today()->addYear()->toDateString(),
            'status' => ProductStatus::Active->value,
            'quantity' => 8,
        ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Product updated.');

        $product->refresh();

        $this->assertSame('Sunflower Oil 750 ml', $product->name);
        $this->assertSame('Cold-pressed for salads.', $product->description);
        $this->assertSame(today()->addYear()->toDateString(), $product->expiration_date->toDateString());
        $this->assertSame(ProductStatus::Active, $product->status);
        $this->assertSame(8, $product->quantity);
    }

    public function test_destroy_deletes_the_product_and_returns_to_the_list(): void
    {
        $product = Product::factory()->create();

        $response = $this->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas('status', 'Product deleted.');

        $this->assertModelMissing($product);
    }

    /**
     * A valid product payload with optional overrides.
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private static function payload(?Product $productFactoryData = null, array $override = []): array
    {
        $product = $productFactoryData ?? Product::factory()->make();

        return array_merge([
            'name' => $product->name,
            'description' => $product->description,
            'expiration_date' => $product->expiration_date->toDateString(),
            'status' => $product->status->value,
            'quantity' => $product->quantity,
        ], $override);
    }
}
