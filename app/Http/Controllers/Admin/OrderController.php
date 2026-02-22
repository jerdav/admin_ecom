<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Ecommerce\OrderService;
use App\Services\Ecommerce\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::query()
            ->with(['user', 'payments'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items', 'payments', 'statusHistory.changedByUser']);

        return view('admin.orders.show', [
            'order' => $order,
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
            'availableTransitions' => $this->availableTransitions($order->status),
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $allowedTransitions = $this->availableTransitions($order->status);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($allowedTransitions)],
        ]);

        try {
            $orders->transitionStatus(
                $order,
                $validated['status'],
                $request->user(),
                'admin_manual_update'
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Statut de commande mis a jour.');
    }

    public function refundPayment(Request $request, Order $order, Payment $payment, PaymentService $payments): RedirectResponse
    {
        if ($payment->order_id !== $order->id) {
            abort(404);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payments->refundPayment($payment, $request->user(), [
                'reason' => $validated['reason'] ?? 'refund_admin',
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }

        return back()->with('success', 'Paiement rembourse avec succes.');
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'pending' => 'Brouillon',
            'paid' => 'Payee',
            'shipped' => 'Expediee',
            'delivered' => 'Livree',
            'cancelled' => 'Annulee',
            'refunded' => 'Remboursee',
            'returned' => 'Retournee',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function paymentStatusLabels(): array
    {
        return [
            'pending' => 'En attente',
            'paid' => 'Paye',
            'failed' => 'Echoue',
            'refunded' => 'Rembourse',
        ];
    }

    /**
     * @return list<string>
     */
    private function availableTransitions(string $status): array
    {
        $map = [
            Order::STATUS_PENDING => [Order::STATUS_PAID, Order::STATUS_CANCELLED],
            Order::STATUS_PAID => [Order::STATUS_SHIPPED, Order::STATUS_REFUNDED, Order::STATUS_CANCELLED],
            Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED, Order::STATUS_RETURNED],
            Order::STATUS_RETURNED => [Order::STATUS_REFUNDED],
            Order::STATUS_DELIVERED => [],
            Order::STATUS_CANCELLED => [],
            Order::STATUS_REFUNDED => [],
        ];

        return $map[$status] ?? [];
    }
}
