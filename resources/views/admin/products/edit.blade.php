@extends('admin.layouts.app', [
    'title' => 'Edition produit',
    'subtitle' => 'Mise a jour du produit et de ses images.',
])

@section('content')
    <section class="panel">
        <div class="section-head">
            <h3 class="section-title">Editer: {{ $product->name }}</h3>
            <a href="{{ route('admin.products') }}">Retour a la liste</a>
        </div>

        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div class="form-grid form-grid-4-edit">
                <label>
                    <span class="field-label-sm">Nom</span>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="field-input-sm">
                </label>
                <label>
                    <span class="field-label-sm">SKU</span>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Optionnel" class="field-input-sm">
                </label>
                <label>
                    <span class="field-label-sm">Categorie</span>
                    <select name="category_id" class="field-select-sm">
                        <option value="">Sans categorie</option>
                        @foreach($categories as $category)
                            @php($selectedCategory = old('category_id', $product->category_id))
                            <option value="{{ $category->id }}" {{ (string) $selectedCategory === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="field-label-sm">Slug (auto)</span>
                    <input type="text" value="{{ $product->slug }}" readonly class="field-input-sm field-input-readonly">
                </label>
            </div>

            <label>
                <span class="field-label-sm">Description</span>
                <textarea name="description" rows="2" class="field-textarea-sm">{{ old('description', $product->description) }}</textarea>
            </label>

            <div class="form-grid form-grid-2">
                <div>
                    <span class="field-label-sm">Photo principale</span>
                    <input type="file" name="main_image_file" accept="image/*" class="field-file-sm">
                    @if($product->main_image_url)
                        @php($mainSrc = str_starts_with($product->main_image_url, 'http') ? $product->main_image_url : Storage::url($product->main_image_url))
                        <div class="thumb-wrap">
                            <img src="{{ $mainSrc }}" alt="{{ $product->name }}" class="image-thumb">
                        </div>
                    @endif
                </div>
                <div>
                    <span class="field-label-sm">Galerie</span>
                    <input type="file" name="gallery_files[]" accept="image/*" multiple class="field-file-sm">
                    @if($product->images->isNotEmpty())
                        <div class="thumb-list">
                            @foreach($product->images as $image)
                                @php($gallerySrc = str_starts_with($image->image_url, 'http') ? $image->image_url : Storage::url($image->image_url))
                                <div class="thumb-item">
                                    <img src="{{ $gallerySrc }}" alt="{{ $image->alt_text ?? $product->name }}" class="image-thumb">
                                    <button
                                        type="submit"
                                        form="delete-image-{{ $image->id }}"
                                        title="Supprimer"
                                        class="thumb-delete"
                                    >x</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="form-grid form-grid-4">
                <label>
                    <span class="field-label-sm">Prix (€)</span>
                    <input type="number" min="0" step="0.01" name="price_eur" value="{{ old('price_eur', number_format($product->price_cents / 100, 2, '.', '')) }}" placeholder="ex: 49,90" required class="field-input-sm">
                </label>
                <label>
                    <span class="field-label-sm">TVA (%)</span>
                    <input type="number" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate) }}" required class="field-input-sm">
                </label>
                <label>
                    <span class="field-label-sm">Stock</span>
                    <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" placeholder="ex: 10" required class="field-input-sm">
                </label>
                <label class="checkbox-inline-sm checkbox-bottom-pad-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ? '1' : '0') ? 'checked' : '' }}>
                    Actif
                </label>
            </div>

            <div class="actions-row mt-14">
                <button type="submit" class="logout logout-inline-sm">Enregistrer</button>
                <button type="submit" form="delete-product-form" class="logout logout-inline-sm logout-danger">Suppr</button>
            </div>
        </form>

        @foreach($product->images as $image)
            <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('admin.products.images.destroy', ['product' => $product, 'image' => $image]) }}" class="hidden-form">
                @csrf
            </form>
        @endforeach

        <form id="delete-product-form" method="POST" action="{{ route('admin.products.destroy', $product) }}" class="hidden-form">
            @csrf
        </form>
    </section>
@endsection
