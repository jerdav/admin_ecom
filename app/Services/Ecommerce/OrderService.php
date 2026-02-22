<?php

namespace App\Services\Ecommerce;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $context
     */
    public function createOrder(User $user, array $items, array $context = []): Order
    {
        if ($items === []) {
            throw new InvalidArgumentException('Order items cannot be empty.');
        }

        [$normalizedItems, $subtotal] = $this->normalizeItems($items);

        $shipping = $this->intValue($context['shipping_cents'] ?? 0);
        $tax = $this->intValue($context['tax_cents'] ?? 0);
        $discount = $this->intValue($context['discount_cents'] ?? 0);
        $total = max(0, $subtotal + $shipping + $tax - $discount);

        $initialStatus = (string) ($context['initial_status'] ?? Order::STATUS_PENDING);
        $statusReason = (string) ($context['status_reason'] ?? 'order_created');

        if (! in_array($initialStatus, [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_RETURNED,
        ], true)) {
            throw new InvalidArgumentException('Invalid initial order status.');
        }

        return DB::transaction(function () use ($user, $context, $normalizedItems, $subtotal, $shipping, $tax, $discount, $total, $initialStatus, $statusReason) {
            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => $initialStatus,
                'currency' => strtoupper((string) ($context['currency'] ?? config('ecommerce.currency', 'EUR'))),
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $shipping,
                'tax_cents' => $tax,
                'discount_cents' => $discount,
                'total_cents' => $total,
                'customer_email' => $user->email,
                'customer_name' => $user->name,
                'metadata' => isset($context['metadata']) && is_array($context['metadata']) ? $context['metadata'] : null,
                'placed_at' => now(),
            ]);

            $order->items()->createMany($normalizedItems);

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => $initialStatus,
                'changed_by' => null,
                'reason' => $statusReason,
                'meta' => null,
            ]);

            return $order->fresh(['items', 'statusHistory']);
        });
    }

    public function recalculateTotals(Order $order): Order
    {
        $subtotal = (int) $order->items()->sum('total_price_cents');
        $total = max(0, $subtotal + $order->shipping_cents + $order->tax_cents - $order->discount_cents);

        $order->forceFill([
            'subtotal_cents' => $subtotal,
            'total_cents' => $total,
        ])->save();

        return $order->fresh(['items', 'statusHistory']);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function transitionStatus(Order $order, string $toStatus, ?User $changedBy = null, ?string $reason = null, array $meta = []): Order
    {
        $toStatus = trim($toStatus);

        if ($toStatus === '') {
            throw new InvalidArgumentException('Target status is required.');
        }

        $fromStatus = (string) $order->status;

        if ($fromStatus === $toStatus) {
            return $order;
        }

        if (! $this->isTransitionAllowed($fromStatus, $toStatus)) {
            throw new InvalidArgumentException("Transition from [{$fromStatus}] to [{$toStatus}] is not allowed.");
        }

        return DB::transaction(function () use ($order, $fromStatus, $toStatus, $changedBy, $reason, $meta) {
            $order->forceFill(['status' => $toStatus])->save();

            $order->statusHistory()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $changedBy?->id,
                'reason' => $reason,
                'meta' => $meta !== [] ? $meta : null,
            ]);

            $this->auditLogs->log(
                action: 'orders.status_changed',
                entityType: 'order',
                entityId: $order->id,
                before: [
                    'status' => $fromStatus,
                ],
                after: [
                    'status' => $toStatus,
                ],
                actor: $changedBy,
                meta: [
                    'reason' => $reason,
                    'extra' => $meta,
                ],
            );

            return $order->fresh(['items', 'statusHistory']);
        });
    }

    private function isTransitionAllowed(string $fromStatus, string $toStatus): bool
    {
        $allowed = [
            Order::STATUS_PENDING => [Order::STATUS_PAID, Order::STATUS_CANCELLED],
            Order::STATUS_PAID => [Order::STATUS_SHIPPED, Order::STATUS_REFUNDED, Order::STATUS_CANCELLED],
            Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED, Order::STATUS_RETURNED],
            Order::STATUS_RETURNED => [Order::STATUS_REFUNDED],
            Order::STATUS_DELIVERED => [],
            Order::STATUS_CANCELLED => [],
            Order::STATUS_REFUNDED => [],
        ];

        return in_array($toStatus, $allowed[$fromStatus] ?? [], true);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];
        $subtotal = 0;

        foreach ($items as $index => $item) {
            $name = trim((string) ($item['product_name'] ?? ''));
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (int) ($item['unit_price_cents'] ?? 0);

            if ($name === '') {
                throw new InvalidArgumentException("Item at index {$index} is missing product_name.");
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException("Item [{$name}] quantity must be greater than zero.");
            }

            if ($unitPrice < 0) {
                throw new InvalidArgumentException("Item [{$name}] unit_price_cents must be a positive integer.");
            }

            $totalPrice = $quantity * $unitPrice;
            $subtotal += $totalPrice;

            $normalized[] = [
                'product_sku' => isset($item['product_sku']) ? trim((string) $item['product_sku']) : null,
                'product_name' => $name,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPrice,
                'total_price_cents' => $totalPrice,
                'metadata' => isset($item['metadata']) && is_array($item['metadata']) ? $item['metadata'] : null,
            ];
        }

        return [$normalized, $subtotal];
    }

    private function intValue(mixed $value): int
    {
        $number = (int) $value;

        if ($number < 0) {
            throw new InvalidArgumentException('Monetary values cannot be negative.');
        }

        return $number;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
