@extends('admin.layouts.app', [
    'title' => 'Commandes',
    'subtitle' => 'Liste des commandes creees apres paiements valides.',
])

@section('content')
    <section class="panel">
        <h3>Commandes recentes</h3>
        <table>
            <thead>
            <tr>
                <th>Numero</th>
                <th>Client</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Paiement</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                @php($latestPayment = $order->payments->sortByDesc('id')->first())
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->customer_name ?? $order->user?->name ?? 'N/D' }}</td>
                    <td>{{ number_format($order->total_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                    <td>
                        <span class="badge status-{{ strtolower($order->status) }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                    </td>
                    <td>
                        @if($latestPayment)
                            <span class="badge status-{{ strtolower($latestPayment->status) }}">{{ $paymentStatusLabels[$latestPayment->status] ?? $latestPayment->status }}</span>
                        @else
                            <span class="text-muted-nd">N/D</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td><a href="{{ route('admin.orders.show', $order) }}">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="7">Aucune commande disponible.</td></tr>
            @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
            <div class="pagination">
                <div>
                    Page {{ $orders->currentPage() }} / {{ $orders->lastPage() }}
                </div>
                <div class="pagination-links">
                    @if($orders->onFirstPage())
                        <span class="pagination-disabled">Precedent</span>
                    @else
                        <a href="{{ $orders->previousPageUrl() }}">Precedent</a>
                    @endif

                    @if($orders->hasMorePages())
                        <a href="{{ $orders->nextPageUrl() }}">Suivant</a>
                    @else
                        <span class="pagination-disabled">Suivant</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection
