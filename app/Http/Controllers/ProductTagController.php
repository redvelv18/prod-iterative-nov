<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductTagController extends Controller
{
    /**
     * Replace the tags of the specified product with the submitted list.
     *
     * New names are created as tags, names missing from the list are
     * detached from the product but stay available to other products.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['required', 'string', 'max:50'],
        ]);

        $product->syncTags($validated['tags'] ?? []);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Tags updated.');
    }

    /**
     * Suggest existing tags matching the text typed in the tag field.
     *
     * Powers the autocomplete on the product page: the browser asks for
     * partial names and receives the tags it may complete to.
     *
     * @return JsonResponse<int, array{tags: list<string>}>
     */
    public function suggest(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        return response()->json([
            'tags' => Tag::search($validated['name'])->pluck('name')->all(),
        ]);
    }
}
