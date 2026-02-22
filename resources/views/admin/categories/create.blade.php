@extends('admin.layouts.app', [
    'title' => 'Nouvelle categorie',
    'subtitle' => 'Creation d une categorie pour le catalogue.',
])

@section('content')
    <section class="panel">
        <div class="section-head">
            <h3 class="section-title">Ajouter une categorie</h3>
            <a href="{{ route('admin.categories') }}">Retour a la liste</a>
        </div>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="form-grid form-narrow">
            @csrf
            <label>
                <span class="field-label">Nom</span>
                <input type="text" name="name" value="{{ old('name') }}" required class="field-input">
            </label>

            <p class="text-muted">Le slug est cree automatiquement a partir du nom.</p>

            <label class="checkbox-inline">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                Active
            </label>

            <div>
                <button type="submit" class="logout logout-inline">Ajouter la categorie</button>
            </div>
        </form>
    </section>
@endsection
