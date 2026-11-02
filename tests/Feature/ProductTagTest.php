<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductTagTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_show_lists_the_products_tags_and_the_tag_form(): void
    {
        $product = Product::factory()->create();
        $product->addTag('Seasonal');

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee(route('products.tags.store', $product), false)
            ->assertSee('Tags')
            ->assertSee('Seasonal')
            ->assertSee('Add a tag');
    }

    public function test_show_explains_that_a_product_without_tags_has_none(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('No tags yet.');
    }

    public function test_posting_a_new_tag_name_attaches_it_and_redirects_back(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('products.tags.store', $product), [
            'name' => 'Organic',
        ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Tag added.');

        $this->assertDatabaseHas('tags', ['name' => 'Organic']);
        $this->assertDatabaseHas('product_tag', ['product_id' => $product->id]);
    }

    public function test_posting_an_existing_tag_name_shares_it_with_another_product(): void
    {
        $first = Product::factory()->create();
        $second = Product::factory()->create();

        $first->addTag('Gift');
        $second->addTag('gift');

        $this->post(route('products.tags.store', $first), ['name' => 'GIFT'])
            ->assertRedirect(route('products.show', $first));

        $this->assertDatabaseCount('tags', 1);
        $this->assertCount(1, $first->fresh()->tags);
    }

    public function test_a_blank_tag_name_is_rejected(): void
    {
        $product = Product::factory()->create();

        $response = $this
            ->from(route('products.show', $product))
            ->post(route('products.tags.store', $product), ['name' => '']);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('tags', 0);
    }

    public function test_a_tag_name_longer_than_fifty_characters_is_rejected(): void
    {
        $product = Product::factory()->create();

        $response = $this
            ->from(route('products.show', $product))
            ->post(route('products.tags.store', $product), [
                'name' => str_repeat('a', 51),
            ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('tags', 0);
    }

    public function test_removing_a_tag_detaches_it_from_the_product(): void
    {
        $product = Product::factory()->create();
        $tag = $product->addTag('Sale');

        $response = $this->delete(
            route('products.tags.destroy', [$product, $tag]),
        );

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Tag removed.');

        $this->assertDatabaseMissing('product_tag', [
            'product_id' => $product->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_removing_a_tag_keeps_it_available_for_other_products(): void
    {
        $first = Product::factory()->create();
        $second = Product::factory()->create();

        $shared = $first->addTag('Gift');
        $second->addTag('Gift');

        $this->delete(route('products.tags.destroy', [$first, $shared]))
            ->assertRedirect(route('products.show', $first));

        $this->assertCount(0, $first->fresh()->tags);
        $this->assertCount(1, $second->fresh()->tags);
        $this->assertDatabaseHas('tags', ['id' => $shared->id]);
    }
}
