@extends('admin.layouts.app', [
    'title' => 'Nouveau produit',
    'subtitle' => 'Creation d un produit dans le catalogue.',
])

@section('content')
    <section class="panel">
        <div class="section-head">
            <h3 class="section-title">Ajouter un produit</h3>
            <a href="{{ route('admin.products') }}">Retour a la liste</a>
        </div>

        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div class="form-grid form-grid-3">
                <label>
                    <span class="field-label">Nom</span>
                    <input type="text" name="name" value="{{ old('name') }}" required class="field-input">
                </label>
                <label>
                    <span class="field-label">SKU</span>
                    <input type="text" name="sku" value="{{ old('sku') }}" placeholder="Optionnel (ex: TSHIRT_BLEU_M)" class="field-input">
                </label>
                <label>
                    <span class="field-label">Categorie</span>
                    <select name="category_id" class="field-select">
                        <option value="">Sans categorie</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <p class="text-muted">Le slug est cree automatiquement a partir du nom.</p>

            <label>
                <span class="field-label">Description</span>
                <textarea name="description" rows="2" class="field-textarea">{{ old('description') }}</textarea>
            </label>

            <div class="form-grid form-grid-2">
                <label>
                    <span class="field-label">Photo principale</span>
                    <input type="file" name="main_image_file" accept="image/*" class="field-file">
                </label>
                <label>
                    <span class="field-label">Galerie</span>
                    <input type="file" name="gallery_files[]" accept="image/*" multiple class="field-file">
                </label>
            </div>

            <div class="form-grid form-grid-4">
                <label>
                    <span class="field-label">Prix (€)</span>
                    <input type="number" min="0" step="0.01" name="price_eur" value="{{ old('price_eur') }}" placeholder="ex: 49,90" required class="field-input">
                </label>
                <label>
                    <span class="field-label">TVA (%)</span>
                    <input type="number" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', 20) }}" required class="field-input">
                </label>
                <label>
                    <span class="field-label">Stock</span>
                    <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity') }}" placeholder="ex: 10" required class="field-input">
                </label>
                <label class="checkbox-inline checkbox-bottom-pad">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    Actif
                </label>
            </div>

            <div>
                <button type="submit" class="logout logout-inline">Ajouter le produit</button>
            </div>
        </form>
    </section>
@endsection
