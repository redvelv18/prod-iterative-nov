<x-layout :title="$product->name">
    <h1>{{ $product->name }}</h1>

    <x-validation-errors />

    <div class="card">
        <dl class="product-details">
            <dt>Description</dt>
            <dd>{{ $product->description ?? '—' }}</dd>

            <dt>Status</dt>
            <dd>
                <span class="status-badge status-{{ $product->status->value }}">
                    {{ $product->status->label() }}
                </span>
            </dd>

            <dt>Expiration date</dt>
            <dd>{{ $product->expiration_date?->format('Y-m-d') ?? '—' }}</dd>

            <dt>Quantity</dt>
            <dd>
                <span class="quantity-value" data-quantity>{{ $product->quantity }} pcs</span>

                <form
                    class="quantity-adjust"
                    action="{{ route('products.quantity', $product) }}"
                    method="POST"
                    data-quantity-form
                >
                    @csrf
                    @method('PATCH')

                    <div class="form-field quantity-adjust-field">
                        <label for="amount">Amount</label>
                        <input
                            type="number"
                            id="amount"
                            name="amount"
                            value="{{ old('amount', 1) }}"
                            min="1"
                            max="1000"
                            @error('amount') class="input-invalid" @enderror
                            required
                        >
                    </div>

                    <div class="quantity-adjust-actions">
                        <button class="btn btn-secondary" type="submit" name="direction" value="increase">
                            <span aria-hidden="true">+</span> Add stock
                        </button>
                        <button class="btn btn-danger" type="submit" name="direction" value="decrease">
                            <span aria-hidden="true">−</span> Remove stock
                        </button>
                    </div>
                </form>

                <p class="quantity-feedback" data-quantity-feedback role="status" aria-live="polite" hidden></p>

                @error('amount')
                    <span class="field-error">{{ $message }}</span>
                @enderror
                @error('direction')
                    <span class="field-error">{{ $message }}</span>
                @enderror

                <p class="muted">
                    Adding stock records a delivery (pievedums); removing stock records a sale (izpirkšana).
                </p>
            </dd>
        </dl>

        <section class="tag-section" aria-labelledby="tags-heading">
            <h2 id="tags-heading">Tags</h2>

            <form
                class="tag-editor"
                action="{{ route('products.tags.update', $product) }}"
                method="POST"
                data-tag-editor
                data-suggest-url="{{ route('products.tags.suggest', $product) }}"
            >
                @csrf
                @method('PUT')

                <ul class="tag-list" data-tag-list aria-label="Current tags" aria-live="polite">
                    @forelse ($product->tags as $tag)
                        <li class="tag-chip" data-tag-chip="{{ $tag->name }}">
                            {{ $tag->name }}

                            <button
                                type="button"
                                class="tag-remove"
                                data-tag-remove="{{ $tag->name }}"
                                aria-label="Remove tag {{ $tag->name }}"
                            >&times;</button>
                        </li>
                    @empty
                        <li class="muted" data-tag-empty>No tags yet.</li>
                    @endforelse
                </ul>

                {{--
                    Mirrors of the chips above. The tag editor keeps them in
                    sync as chips are added or removed, so the Update button
                    submits the whole list in one PUT request. Without
                    JavaScript the visible input still posts its own text.
                --}}
                <div class="tag-values" data-tag-values>
                    @foreach ($product->tags as $tag)
                        <input type="hidden" name="tags[]" value="{{ $tag->name }}" data-tag-value>
                    @endforeach
                </div>

                <div class="tag-combobox">
                    <label for="tag-name">Add a tag</label>

                    <div class="tag-combobox-row">
                        <input
                            type="text"
                            id="tag-name"
                            name="tags[]"
                            value=""
                            maxlength="50"
                            placeholder="Start typing a tag"
                            autocomplete="off"
                            data-tag-input
                            role="combobox"
                            aria-expanded="false"
                            aria-controls="tag-suggestions"
                            aria-autocomplete="list"
                        >
                        <button class="btn btn-secondary" type="button" data-tag-add>Add</button>
                    </div>

                    <ul
                        class="tag-suggestions"
                        id="tag-suggestions"
                        role="listbox"
                        data-tag-suggestions
                        hidden
                    ></ul>
                </div>

                <button class="btn btn-primary tag-update" type="submit" data-tag-update>Update</button>
            </form>
        </section>

        <p class="form-actions">
            <a class="btn btn-secondary" href="{{ route('products.edit', $product) }}">Edit</a>

            <form class="inline" action="{{ route('products.destroy', $product) }}" method="POST"
                  onsubmit="return confirm('Delete &ldquo;{{ $product->name }}&rdquo;? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Delete</button>
            </form>

            <a class="btn btn-secondary" href="{{ route('products.index') }}">Back to products</a>
        </p>
    </div>
</x-layout>
