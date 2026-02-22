@extends('admin.layouts.app', [
    'title' => 'Tableau de bord',
    'subtitle' => 'Vue operationnelle du back-office e-commerce.',
])

@section('content')
    <section class="grid">
        <article class="kpi"><span>Commandes totales</span><h2>{{ $stats['orders_total'] }}</h2></article>
        <article class="kpi"><span>Commandes payees</span><h2>{{ $stats['orders_paid'] }}</h2></article>
        <article class="kpi"><span>Paniers actifs</span><h2>{{ $stats['active_carts'] }}</h2></article>
        <article class="kpi"><span>CA confirme</span><h2>{{ $stats['revenue_eur'] }} EUR</h2></article>
        <article class="kpi"><span>Clients</span><h2>{{ $stats['customers'] }}</h2></article>
        <article class="kpi"><span>Administrateurs</span><h2>{{ $stats['admins'] }}</h2></article>
        <article class="kpi"><span>Paiements echoues</span><h2>{{ $stats['payments_failed'] }}</h2></article>
        <article class="kpi"><span>Paiements rembourses</span><h2>{{ $stats['payments_refunded'] }}</h2></article>
    </section>

    <section class="panel-wrap">
        <article class="panel">
            <h3>Dernieres commandes</h3>
            <table>
                <thead>
                <tr>
                    <th>Numero</th>
                    <th>Client</th>
                    <th>Total</th>
                    <th>Statut</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentOrders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->customer_name ?? $order->user?->name ?? 'N/D' }}</td>
                        <td>{{ number_format($order->total_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                        <td>
                            <span class="badge status-{{ strtolower($order->status) }}">{{ $orderStatusLabels[$order->status] ?? $order->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucune commande pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>

        <aside class="panel">
            <h3>Journal d'audit recent</h3>
            <div class="logs">
                @forelse($recentAuditLogs as $log)
                    <article class="log-item">
                        <strong>{{ $auditActionLabels[$log->action] ?? $log->action }}</strong>
                        <div class="log-meta">Entite: {{ $auditEntityLabels[$log->entity_type] ?? ($log->entity_type ?? 'n/d') }}#{{ $log->entity_id ?? '-' }}</div>
                        <div class="log-meta">Par: {{ $log->user?->email ?? 'systeme' }} | {{ $log->created_at?->format('d/m/Y H:i') }}</div>
                    </article>
                @empty
                    <p class="text-muted-dashboard">Aucune entree d'audit pour le moment.</p>
                @endforelse
            </div>
        </aside>
    </section>
@endsection
