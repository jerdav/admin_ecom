@extends('admin.layouts.app', [
    'title' => 'Produits',
    'subtitle' => 'Liste du catalogue produits.',
])

@section('content')
    <section class="panel">
        <div class="section-head">
            <h3 class="section-title">Produits existants</h3>
            <a href="{{ route('admin.products.create') }}" class="logout logout-link">Nouveau produit</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Nom</th>
                    <th>SKU</th>
                    <th>Categorie</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            @if($product->main_image_url)
                                @php($imgSrc = str_starts_with($product->main_image_url, 'http') ? $product->main_image_url : Storage::url($product->main_image_url))
                                <img src="{{ $imgSrc }}" alt="{{ $product->name }}" class="image-thumb-sm">
                            @else
                                <span class="text-muted-nd">N/D</span>
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->sku ?? 'N/D' }}</td>
                        <td>{{ $product->category?->name ?? 'Sans categorie' }}</td>
                        <td>{{ number_format($product->price_cents / 100, 2, ',', ' ') }} €</td>
                        <td>{{ $product->stock_quantity }}</td>
                        <td>
                            <span class="badge {{ $product->is_active ? 'status-paid' : 'status-failed' }}">
                                {{ $product->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.products.edit', $product) }}">Editer</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Aucun produit configure.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if($products->hasPages())
            <div class="pagination">
                <div>
                    Page {{ $products->currentPage() }} / {{ $products->lastPage() }}
                </div>
                <div class="pagination-links">
                    @if($products->onFirstPage())
                        <span class="pagination-disabled">Precedent</span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}">Precedent</a>
                    @endif

                    @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}">Suivant</a>
                    @else
                        <span class="pagination-disabled">Suivant</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection
