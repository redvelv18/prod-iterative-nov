<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_or_create_stores_a_new_tag(): void
    {
        $tag = Tag::findOrCreate('Organic');

        $this->assertSame('Organic', $tag->name);
        $this->assertDatabaseHas('tags', ['name' => 'Organic']);
        $this->assertDatabaseCount('tags', 1);
    }

    public function test_find_or_create_trims_the_surrounding_whitespace(): void
    {
        $tag = Tag::findOrCreate('   Local   ');

        $this->assertSame('Local', $tag->name);
        $this->assertDatabaseCount('tags', 1);
    }

    public function test_find_or_create_reuses_an_existing_tag_ignoring_letter_case(): void
    {
        $existing = Tag::findOrCreate('Seasonal');

        $same = Tag::findOrCreate('SEASONAL');

        $this->assertTrue($existing->is($same));
        $this->assertDatabaseCount('tags', 1);
    }

    public function test_find_or_create_creates_separate_tags_for_different_names(): void
    {
        Tag::findOrCreate('Sale');
        Tag::findOrCreate('Clearance');

        $this->assertDatabaseCount('tags', 2);
    }

    public function test_a_tag_can_belong_to_several_products(): void
    {
        $tag = Tag::findOrCreate('Vegan');
        $first = Product::factory()->create();
        $second = Product::factory()->create();

        $first->addTag($tag->name);
        $second->addTag($tag->name);

        $this->assertDatabaseCount('tags', 1);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $tag->fresh()->products->pluck('id')->all(),
        );
    }

    public function test_products_relation_lists_the_attached_tags(): void
    {
        $product = Product::factory()->create();
        $product->addTag('Gift');
        $product->addTag('Seasonal');

        $this->assertEqualsCanonicalizing(
            ['Gift', 'Seasonal'],
            $product->fresh()->tags->pluck('name')->all(),
        );
    }
}
