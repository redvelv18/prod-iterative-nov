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

            @if ($product->tags->isEmpty())
                <p class="muted">No tags yet.</p>
            @else
                <ul class="tag-list">
                    @foreach ($product->tags as $tag)
                        <li class="tag-chip">
                            {{ $tag->name }}

                            <form
                                class="inline"
                                action="{{ route('products.tags.destroy', [$product, $tag]) }}"
                                method="POST"
                                onsubmit="return confirm('Remove tag &ldquo;{{ $tag->name }}&rdquo; from this product?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    class="tag-remove"
                                    type="submit"
                                    aria-label="Remove tag {{ $tag->name }}"
                                >&times;</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form
                class="tag-form"
                action="{{ route('products.tags.store', $product) }}"
                method="POST"
            >
                @csrf

                <div class="form-field tag-form-field">
                    <label for="tag-name">Add a tag</label>
                    <input
                        type="text"
                        id="tag-name"
                        name="name"
                        value="{{ old('name') }}"
                        maxlength="50"
                        placeholder="e.g. organic"
                        @error('name') class="input-invalid" @enderror
                    >
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <button class="btn btn-secondary" type="submit">Add tag</button>
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
