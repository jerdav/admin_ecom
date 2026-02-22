@extends('admin.layouts.app', [
    'title' => 'Tableau de bord',
    'subtitle' => 'Vue opérationnelle du back-office e-commerce.',
])

@section('content')
    <section class="grid">
        <article class="kpi kpi--accent">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:shopping-cart-solid" class="kpi-icon"></iconify-icon>
                <span>Commandes totales</span>
            </div>
            <h2>{{ $stats['orders_total'] }}</h2>
        </article>
        <article class="kpi kpi--good">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:check-circle-solid" class="kpi-icon"></iconify-icon>
                <span>Commandes payées</span>
            </div>
            <h2>{{ $stats['orders_paid'] }}</h2>
        </article>
        <article class="kpi kpi--blue">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:shopping-bag-solid" class="kpi-icon"></iconify-icon>
                <span>Paniers actifs</span>
            </div>
            <h2>{{ $stats['active_carts'] }}</h2>
        </article>
        <article class="kpi kpi--good">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:banknotes-solid" class="kpi-icon"></iconify-icon>
                <span>CA confirmé</span>
            </div>
            <h2>{{ $stats['revenue_eur'] }} €</h2>
        </article>
        <article class="kpi kpi--purple">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:users-solid" class="kpi-icon"></iconify-icon>
                <span>Clients</span>
            </div>
            <h2>{{ $stats['customers'] }}</h2>
        </article>
        <article class="kpi kpi--blue">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:shield-check-solid" class="kpi-icon"></iconify-icon>
                <span>Administrateurs</span>
            </div>
            <h2>{{ $stats['admins'] }}</h2>
        </article>
        <article class="kpi kpi--bad">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:x-circle-solid" class="kpi-icon"></iconify-icon>
                <span>Paiements échoués</span>
            </div>
            <h2>{{ $stats['payments_failed'] }}</h2>
        </article>
        <article class="kpi kpi--warn">
            <div class="kpi-head">
                <iconify-icon icon="heroicons:arrow-uturn-left-solid" class="kpi-icon"></iconify-icon>
                <span>Paiements remboursés</span>
            </div>
            <h2>{{ $stats['payments_refunded'] }}</h2>
        </article>
    </section>

    <section class="panel-wrap">
        <article class="panel">
            <h3>Dernières commandes</h3>
            <table>
                <thead>
                <tr>
                    <th>Numéro</th>
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
            <h3>Journal d'audit récent</h3>
            <div class="logs">
                @forelse($recentAuditLogs as $log)
                    <article class="log-item">
                        <strong>{{ $auditActionLabels[$log->action] ?? $log->action }}</strong>
                        <div class="log-meta">Entité : {{ $auditEntityLabels[$log->entity_type] ?? ($log->entity_type ?? 'n/d') }}#{{ $log->entity_id ?? '-' }}</div>
                        <div class="log-meta">Par : {{ $log->user?->email ?? 'système' }} | {{ $log->created_at?->format('d/m/Y H:i') }}</div>
                    </article>
                @empty
                    <p class="text-muted-dashboard">Aucune entrée d'audit pour le moment.</p>
                @endforelse
            </div>
        </aside>
    </section>
@endsection
