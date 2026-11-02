<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FrontendAssetsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_stylesheet_and_script_entries_are_registered_with_vite(): void
    {
        $manifest = $this->manifest();

        $this->assertArrayHasKey('resources/css/app.css', $manifest);
        $this->assertArrayHasKey('resources/js/app.js', $manifest);

        $this->assertFileExists(public_path('build/'.$manifest['resources/css/app.css']['file']));
        $this->assertFileExists(public_path('build/'.$manifest['resources/js/app.js']['file']));
    }

    public function test_the_quantity_script_is_bundled_into_the_script_entry(): void
    {
        $manifest = $this->manifest();
        $bundle = public_path('build/'.$manifest['resources/js/app.js']['file']);

        $this->assertFileExists($bundle);

        $bundleContents = file_get_contents($bundle);

        $this->assertStringContainsString(
            'data-quantity-form',
            $bundleContents,
            'resources/js/quantity.js was not bundled by Vite into resources/js/app.js.',
        );
        $this->assertStringContainsString(
            'data-tag-editor',
            $bundleContents,
            'resources/js/tag-editor.js was not bundled by Vite into resources/js/app.js.',
        );
    }

    public function test_the_product_page_loads_the_bundled_stylesheet_and_script(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('/build/assets/', false)
            ->assertSee('type="module"', false)
            ->assertSee('data-quantity-form', false);
    }

    /**
     * The Vite manifest written by `npm run build`, keyed by entry source.
     *
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $path = public_path('build/manifest.json');

        $this->assertFileExists($path, 'Vite manifest missing, run `npm run build` first.');

        return json_decode(file_get_contents($path), true);
    }
}
