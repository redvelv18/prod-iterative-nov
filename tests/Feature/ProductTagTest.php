<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductTagTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_show_renders_the_tag_editor_with_the_current_tags(): void
    {
        $product = Product::factory()->create();
        $product->syncTags(['Seasonal']);

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee(route('products.tags.update', $product), false)
            ->assertSee('data-tag-editor', false)
            ->assertSee('data-tag-input', false)
            ->assertSee('data-tag-suggestions', false)
            ->assertSee('data-suggest-url="'.route('products.tags.suggest', $product), false)
            ->assertSee('data-tag-chip="Seasonal"', false)
            ->assertSee('Update');
    }

    public function test_show_explains_that_a_product_without_tags_has_none(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('No tags yet.');
    }

    public function test_put_attaches_existing_and_new_tags_in_one_request(): void
    {
        $product = Product::factory()->create();
        Tag::findOrCreate('Sale');

        $response = $this->put(route('products.tags.update', $product), [
            'tags' => ['Sale', 'Organic'],
        ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Tags updated.');

        $this->assertEqualsCanonicalizing(
            ['Sale', 'Organic'],
            $product->fresh()->tags->pluck('name')->all(),
        );
        $this->assertDatabaseCount('tags', 2);
    }

    public function test_put_shares_a_tag_between_products_instead_of_duplicating_it(): void
    {
        $first = Product::factory()->create();
        $second = Product::factory()->create();

        $this->put(route('products.tags.update', $first), ['tags' => ['Gift']]);
        $this->put(route('products.tags.update', $second), ['tags' => ['gift']]);

        $this->assertDatabaseCount('tags', 1);
        $this->assertCount(1, $first->fresh()->tags);
        $this->assertCount(1, $second->fresh()->tags);
    }

    public function test_put_detaches_the_tags_left_out_of_the_submitted_list(): void
    {
        $product = Product::factory()->create();
        $product->syncTags(['Sale', 'Gift']);

        $this->put(route('products.tags.update', $product), ['tags' => ['Sale']])
            ->assertRedirect(route('products.show', $product));

        $this->assertEqualsCanonicalizing(
            ['Sale'],
            $product->fresh()->tags->pluck('name')->all(),
        );
        $this->assertDatabaseHas('tags', ['name' => 'Gift']);
    }

    public function test_put_with_an_empty_list_clears_the_tags(): void
    {
        $product = Product::factory()->create();
        $product->syncTags(['Sale']);

        $this->put(route('products.tags.update', $product), ['tags' => []])
            ->assertRedirect(route('products.show', $product));

        $this->assertCount(0, $product->fresh()->tags);
    }

    public function test_put_accepts_a_tag_typed_without_javascript(): void
    {
        $product = Product::factory()->create();

        $response = $this
            ->from(route('products.show', $product))
            ->put(route('products.tags.update', $product), ['tags' => ['Local']]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Tags updated.');

        $this->assertEqualsCanonicalizing(
            ['Local'],
            $product->fresh()->tags->pluck('name')->all(),
        );
    }

    public function test_put_rejects_a_blank_tag_name(): void
    {
        $product = Product::factory()->create();

        $response = $this
            ->from(route('products.show', $product))
            ->put(route('products.tags.update', $product), ['tags' => ['']]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('tags.0');

        $this->assertCount(0, $product->fresh()->tags);
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_put_rejects_a_tag_name_longer_than_fifty_characters(): void
    {
        $product = Product::factory()->create();

        $response = $this
            ->from(route('products.show', $product))
            ->put(route('products.tags.update', $product), [
                'tags' => [str_repeat('a', 51)],
            ]);

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('tags.0');

        $this->assertDatabaseCount('tags', 0);
    }

    public function test_suggest_returns_matching_tags_as_json(): void
    {
        Tag::findOrCreate('Seasonal');
        Tag::findOrCreate('Clearance');
        Tag::findOrCreate('Gift');

        $product = Product::factory()->create();

        $response = $this->getJson(
            route('products.tags.suggest', [$product, 'name' => 'sea']),
        );

        $response->assertOk()
            ->assertExactJson(['tags' => ['Seasonal']]);
    }

    public function test_suggest_still_returns_the_products_own_tags_for_the_editor_to_filter(): void
    {
        $product = Product::factory()->create();
        $product->syncTags(['Seasonal']);
        Tag::findOrCreate('Seasonal Box');

        $response = $this->getJson(
            route('products.tags.suggest', [$product, 'name' => 'seas']),
        );

        $response->assertOk()
            ->assertExactJson(['tags' => ['Seasonal', 'Seasonal Box']]);
    }

    public function test_suggest_requires_a_query(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson(route('products.tags.suggest', $product));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }
}
