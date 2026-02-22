@extends('admin.layouts.app', [
    'title' => 'Commande ' . $order->order_number,
    'subtitle' => 'Detail commande, articles, paiements et historique de statut.',
])

@section('content')
    <section class="panel panel-mb">
        <h3>Actions</h3>
        <div class="panel-wrap panel-wrap-half form-grid-gap-12">
            <form method="POST" action="{{ route('admin.orders.status.update', $order) }}" class="order-action-card">
                @csrf
                <label for="status" class="order-action-label">Changer le statut</label>
                <select id="status" name="status" class="order-select" {{ $availableTransitions === [] ? 'disabled' : '' }}>
                    @if($availableTransitions === [])
                        <option>Aucune transition disponible</option>
                    @else
                        @foreach($availableTransitions as $status)
                            <option value="{{ $status }}">{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    @endif
                </select>
                <button type="submit" class="logout logout-soft" {{ $availableTransitions === [] ? 'disabled' : '' }}>Mettre a jour</button>
            </form>

            <div class="order-action-card">
                <p class="provider-add-title">Remboursement paiement</p>
                @php($paidPayments = $order->payments->where('status', 'paid'))
                @if($paidPayments->isEmpty())
                    <p class="text-muted-md">Aucun paiement paye a rembourser.</p>
                @else
                    @foreach($paidPayments as $payment)
                        <form method="POST" action="{{ route('admin.orders.payments.refund', [$order, $payment]) }}" class="checkbox-inline mb-8">
                            @csrf
                            <input type="text" name="reason" placeholder="Motif (optionnel)" class="order-reason">
                            <button type="submit" class="logout logout-link">Rembourser</button>
                        </form>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <section class="panel-wrap panel-wrap-half panel-mb">
        <article class="panel">
            <h3>Informations</h3>
            <p><strong>Numero:</strong> {{ $order->order_number }}</p>
            <p><strong>Client:</strong> {{ $order->customer_name ?? $order->user?->name ?? 'N/D' }}</p>
            <p><strong>Email:</strong> {{ $order->customer_email ?? $order->user?->email ?? 'N/D' }}</p>
            <p><strong>Statut:</strong> <span class="badge status-{{ strtolower($order->status) }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></p>
            <p><strong>Date:</strong> {{ $order->created_at?->format('d/m/Y H:i') }}</p>
        </article>

        <article class="panel">
            <h3>Montants</h3>
            <p><strong>Sous-total:</strong> {{ number_format($order->subtotal_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</p>
            <p><strong>Livraison:</strong> {{ number_format($order->shipping_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</p>
            <p><strong>Taxes:</strong> {{ number_format($order->tax_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</p>
            <p><strong>Remise:</strong> {{ number_format($order->discount_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</p>
            <p><strong>Total:</strong> <strong>{{ number_format($order->total_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</strong></p>
        </article>
    </section>

    <section class="panel panel-mb">
        <h3>Articles</h3>
        <table>
            <thead>
            <tr>
                <th>SKU</th>
                <th>Nom</th>
                <th>Quantite</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @forelse($order->items as $item)
                <tr>
                    <td>{{ $item->product_sku ?? 'N/D' }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                    <td>{{ number_format($item->total_price_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Aucun article.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="panel-wrap panel-wrap-half">
        <article class="panel">
            <h3>Paiements</h3>
            <table>
                <thead>
                <tr>
                    <th>Moyen</th>
                    <th>Statut</th>
                    <th>Montant</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @forelse($order->payments as $payment)
                    <tr>
                        <td>{{ strtoupper($payment->provider) }}</td>
                        <td><span class="badge status-{{ strtolower($payment->status) }}">{{ $paymentStatusLabels[$payment->status] ?? $payment->status }}</span></td>
                        <td>{{ number_format($payment->amount_cents / 100, 2, ',', ' ') }} {{ $payment->currency }}</td>
                        <td>{{ $payment->processed_at?->format('d/m/Y H:i') ?? 'N/D' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucun paiement enregistre.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>

        <article class="panel">
            <h3>Historique des statuts</h3>
            <table>
                <thead>
                <tr>
                    <th>De</th>
                    <th>Vers</th>
                    <th>Par</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @forelse($order->statusHistory as $history)
                    <tr>
                        <td>{{ $history->from_status ? ($statusLabels[$history->from_status] ?? $history->from_status) : '-' }}</td>
                        <td>{{ $statusLabels[$history->to_status] ?? $history->to_status }}</td>
                        <td>{{ $history->changedByUser?->email ?? 'systeme' }}</td>
                        <td>{{ $history->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucun historique.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    </section>
@endsection
