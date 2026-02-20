<?php

namespace App\Services\Ecommerce;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        private readonly FeatureFlagService $featureFlags,
        private readonly OrderService $orders,
        private readonly AuditLogService $auditLogs,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createMockPayment(Order $order, array $payload = []): Payment
    {
        if (! $this->featureFlags->isEnabled('payment.mock', 'global', true)) {
            throw new InvalidArgumentException('Mock payment provider is disabled.');
        }

        if (! in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID], true)) {
            throw new InvalidArgumentException('Payment cannot be created for the current order status.');
        }

        $status = $this->resolveRequestedStatus((string) ($payload['status'] ?? Payment::STATUS_PAID));

        return DB::transaction(function () use ($order, $payload, $status) {
            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider' => Payment::PROVIDER_MOCK,
                'status' => Payment::STATUS_PENDING,
                'amount_cents' => (int) ($payload['amount_cents'] ?? $order->total_cents),
                'currency' => strtoupper((string) ($payload['currency'] ?? $order->currency)),
                'transaction_id' => null,
                'failure_reason' => null,
                'meta' => isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : null,
                'processed_at' => null,
            ]);

            return $this->finalizeMockPayment($payment, $status, $payload);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function refundPayment(Payment $payment, ?User $changedBy = null, array $payload = []): Payment
    {
        if ($payment->status !== Payment::STATUS_PAID) {
            throw new InvalidArgumentException('Only paid payments can be refunded.');
        }

        return DB::transaction(function () use ($payment, $changedBy, $payload) {
            $before = [
                'status' => $payment->status,
            ];

            $payment->forceFill([
                'status' => Payment::STATUS_REFUNDED,
                'failure_reason' => null,
                'processed_at' => now(),
                'meta' => $this->mergeMeta($payment->meta, [
                    'refund' => [
                        'reason' => $payload['reason'] ?? null,
                        'requested_at' => now()->toIso8601String(),
                    ],
                ]),
            ])->save();

            $this->orders->transitionStatus(
                $payment->order()->firstOrFail(),
                Order::STATUS_REFUNDED,
                $changedBy,
                'payment_refunded',
                ['payment_id' => $payment->id]
            );

            $this->auditLogs->log(
                action: 'payments.refunded',
                entityType: 'payment',
                entityId: $payment->id,
                before: $before,
                after: [
                    'status' => $payment->status,
                ],
                actor: $changedBy,
                meta: [
                    'reason' => $payload['reason'] ?? null,
                    'order_id' => $payment->order_id,
                ],
            );

            return $payment->fresh();
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function finalizeMockPayment(Payment $payment, string $status, array $payload): Payment
    {
        $order = $payment->order()->firstOrFail();

        if ($status === Payment::STATUS_PAID && $order->status !== Order::STATUS_PENDING) {
            throw new InvalidArgumentException('Order must be pending to confirm a payment.');
        }

        if ($status === Payment::STATUS_REFUNDED) {
            throw new InvalidArgumentException('Direct refunded status is not allowed. Use refundPayment().');
        }

        $transactionId = $this->generateTransactionId();

        $payment->forceFill([
            'status' => $status,
            'transaction_id' => $transactionId,
            'failure_reason' => $status === Payment::STATUS_FAILED ? (string) ($payload['failure_reason'] ?? 'mock_declined') : null,
            'processed_at' => now(),
        ])->save();

        if ($status === Payment::STATUS_PAID) {
            $this->orders->transitionStatus(
                $order,
                Order::STATUS_PAID,
                null,
                'payment_confirmed',
                ['payment_id' => $payment->id, 'provider' => Payment::PROVIDER_MOCK]
            );
        }

        return $payment->fresh();
    }

    private function resolveRequestedStatus(string $status): string
    {
        $status = trim(strtolower($status));

        if (! in_array($status, [Payment::STATUS_PAID, Payment::STATUS_FAILED], true)) {
            throw new InvalidArgumentException('Unsupported payment status for mock provider.');
        }

        return $status;
    }

    /**
     * @param array<string, mixed>|null $existing
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function mergeMeta(?array $existing, array $extra): array
    {
        return array_merge($existing ?? [], $extra);
    }

    private function generateTransactionId(): string
    {
        return 'MOCK-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
