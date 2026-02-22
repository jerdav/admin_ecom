<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Cart;
use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $ordersPaid = Order::query()->whereIn('status', [
            Order::STATUS_PAID,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_REFUNDED,
        ])->count();

        $ordersTotal = Order::query()->count();

        $revenueCents = (int) Order::query()
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
            ->sum('total_cents');

        $customers = User::query()->where('role', User::ROLE_CUSTOMER)->count();
        $admins = User::query()->where('role', User::ROLE_ADMIN)->count();

        $activeCarts = Cart::query()->whereNull('converted_at')->count();
        $paymentsFailed = CheckoutAttempt::query()->where('status', CheckoutAttempt::STATUS_FAILED)->count()
            + Payment::query()->where('status', Payment::STATUS_FAILED)->count();
        $paymentsRefunded = Payment::query()->where('status', Payment::STATUS_REFUNDED)->count();

        $recentOrders = Order::query()
            ->with(['user', 'payments'])
            ->latest('id')
            ->limit(8)
            ->get();

        $recentAuditLogs = AuditLog::query()
            ->with('user')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'orders_total' => $ordersTotal,
                'orders_paid' => $ordersPaid,
                'active_carts' => $activeCarts,
                'revenue_eur' => number_format($revenueCents / 100, 2, ',', ' '),
                'customers' => $customers,
                'admins' => $admins,
                'payments_failed' => $paymentsFailed,
                'payments_refunded' => $paymentsRefunded,
            ],
            'recentOrders' => $recentOrders,
            'recentAuditLogs' => $recentAuditLogs,
            'orderStatusLabels' => [
                'pending' => 'Brouillon',
                'paid' => 'Payee',
                'shipped' => 'Expediee',
                'delivered' => 'Livree',
                'cancelled' => 'Annulee',
                'refunded' => 'Remboursee',
                'returned' => 'Retournee',
            ],
            'auditActionLabels' => [
                'orders.status_changed' => 'Changement de statut commande',
                'payments.refunded' => 'Remboursement paiement',
                'payments.failed' => 'Paiement echoue',
                'settings.critical_updated' => 'Mise a jour parametre critique',
            ],
            'auditEntityLabels' => [
                'order' => 'Commande',
                'payment' => 'Paiement',
                'checkout_attempt' => 'Tentative paiement',
                'setting' => 'Parametre',
            ],
        ]);
    }
}
