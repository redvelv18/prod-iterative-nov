<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductTagController extends Controller
{
    /**
     * Attach a tag to the specified product.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $product->addTag($validated['name']);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Tag added.');
    }

    /**
     * Detach the specified tag from the specified product.
     *
     * The tag itself stays available for every other product.
     */
    public function destroy(Product $product, Tag $tag): RedirectResponse
    {
        $product->removeTag($tag);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Tag removed.');
    }
}
