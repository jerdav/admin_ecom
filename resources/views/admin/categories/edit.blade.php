@extends('admin.layouts.app', [
    'title' => 'Edition categorie',
    'subtitle' => 'Mise a jour de la categorie.',
])

@section('content')
    <section class="panel">
        <div class="section-head">
            <h3 class="section-title">Editer: {{ $category->name }}</h3>
            <a href="{{ route('admin.categories') }}">Retour a la liste</a>
        </div>

        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="form-grid form-narrow">
            @csrf
            <label>
                <span class="field-label">Nom</span>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="field-input">
            </label>

            <label>
                <span class="field-label">Slug (auto)</span>
                <input type="text" value="{{ $category->slug }}" readonly class="field-input field-input-readonly">
            </label>

            <p class="text-muted">Produits lies: {{ $category->products_count }}</p>

            <label class="checkbox-inline">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ? '1' : '0') ? 'checked' : '' }}>
                Active
            </label>

            <div class="actions-row">
                <button type="submit" class="logout logout-inline">Enregistrer</button>
                <button type="submit" form="delete-category-form" class="logout logout-inline logout-danger">Suppr</button>
            </div>
        </form>

        <form id="delete-category-form" method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="hidden-form">
            @csrf
        </form>
    </section>
@endsection
