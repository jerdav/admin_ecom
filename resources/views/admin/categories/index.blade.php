@extends('admin.layouts.app', [
    'title' => 'Categories',
    'subtitle' => 'Liste des categories du catalogue.',
])

@section('content')
    <section class="panel">
        <div class="section-head">
            <h3 class="section-title">Categories existantes</h3>
            <a href="{{ route('admin.categories.create') }}" class="logout logout-link">Nouvelle categorie</a>
        </div>

        <table>
            <thead>
            <tr>
                <th>Nom</th>
                <th>Slug</th>
                <th>Produits</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ $category->products_count }}</td>
                    <td>
                        <span class="badge {{ $category->is_active ? 'status-paid' : 'status-failed' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td><a href="{{ route('admin.categories.edit', $category) }}">Editer</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Aucune categorie disponible.</td></tr>
            @endforelse
            </tbody>
        </table>

        @if($categories->hasPages())
            <div class="pagination">
                <div>Page {{ $categories->currentPage() }} / {{ $categories->lastPage() }}</div>
                <div class="pagination-links">
                    @if($categories->onFirstPage())
                        <span class="pagination-disabled">Precedent</span>
                    @else
                        <a href="{{ $categories->previousPageUrl() }}">Precedent</a>
                    @endif

                    @if($categories->hasMorePages())
                        <a href="{{ $categories->nextPageUrl() }}">Suivant</a>
                    @else
                        <span class="pagination-disabled">Suivant</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection
