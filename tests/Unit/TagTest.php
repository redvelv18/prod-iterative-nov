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

        $first->syncTags([$tag->name]);
        $second->syncTags([$tag->name]);

        $this->assertDatabaseCount('tags', 1);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $tag->fresh()->products->pluck('id')->all(),
        );
    }

    public function test_search_matches_tag_names_ignoring_letter_case(): void
    {
        Tag::findOrCreate('Seasonal');
        Tag::findOrCreate('Clearance');
        Tag::findOrCreate('Gift');

        $matches = Tag::search('sea');

        $this->assertSame(['Seasonal'], $matches->pluck('name')->all());
    }

    public function test_search_returns_several_matches_in_alphabetical_order(): void
    {
        Tag::findOrCreate('Bakery Sale');
        Tag::findOrCreate('Autumn Sale');
        Tag::findOrCreate('Clearance');

        $matches = Tag::search('sale');

        $this->assertSame(['Autumn Sale', 'Bakery Sale'], $matches->pluck('name')->all());
    }

    public function test_search_ignores_sql_wildcards_in_the_query(): void
    {
        Tag::findOrCreate('Sale');
        Tag::findOrCreate('Gift');

        // Wildcards are stripped, so they narrow the match instead of widening it.
        $this->assertCount(1, Tag::search('sa%'));
        $this->assertCount(0, Tag::search('%'));
    }

    public function test_search_returns_nothing_for_a_blank_query(): void
    {
        Tag::findOrCreate('Sale');

        $this->assertCount(0, Tag::search('   '));
    }

    public function test_search_caps_the_number_of_suggestions(): void
    {
        foreach (range(1, 15) as $index) {
            Tag::findOrCreate(sprintf('Discount %02d', $index));
        }

        $this->assertCount(10, Tag::search('discount'));
    }
}
