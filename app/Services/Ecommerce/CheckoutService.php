<?php

namespace App\Services\Ecommerce;

use App\Models\Cart;
use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly OrderService $orders,
        private readonly CustomerProfileService $profiles,
        private readonly FeatureFlagService $featureFlags,
        private readonly AuditLogService $auditLogs,
    ) {
    }

    /**
     * @param array<string, mixed> $paymentPayload
     * @param array<string, mixed> $profileData
     * @return array{status: string, cart: Cart, attempt: CheckoutAttempt, order: Order|null, payment: Payment|null}
     */
    public function processMockCheckout(User $user, array $paymentPayload = [], array $profileData = []): array
    {
        if (! $this->featureFlags->isEnabled('payment.mock', 'global', true)) {
            throw new InvalidArgumentException('Le paiement mock est desactive.');
        }

        $cart = $this->carts->activeCart($user);

        if (! $cart || $cart->items->isEmpty()) {
            throw new InvalidArgumentException('Aucun panier actif a payer.');
        }

        $status = $this->resolveStatus((string) ($paymentPayload['status'] ?? CheckoutAttempt::STATUS_PAID));

        return DB::transaction(function () use ($user, $cart, $status, $paymentPayload, $profileData) {
            $this->profiles->syncFromCheckout($user, $profileData);

            $attempt = CheckoutAttempt::query()->create([
                'user_id' => $user->id,
                'cart_id' => $cart->id,
                'order_id' => null,
                'provider' => Payment::PROVIDER_MOCK,
                'status' => $status,
                'amount_cents' => $cart->total_cents,
                'currency' => $cart->currency,
                'failure_reason' => $status === CheckoutAttempt::STATUS_FAILED
                    ? (string) ($paymentPayload['failure_reason'] ?? 'mock_declined')
                    : null,
                'meta' => isset($paymentPayload['meta']) && is_array($paymentPayload['meta']) ? $paymentPayload['meta'] : null,
                'processed_at' => now(),
            ]);

            if ($status === CheckoutAttempt::STATUS_FAILED) {
                $this->auditLogs->log(
                    action: 'payments.failed',
                    entityType: 'checkout_attempt',
                    entityId: $attempt->id,
                    before: null,
                    after: [
                        'status' => $attempt->status,
                        'amount_cents' => $attempt->amount_cents,
                    ],
                    actor: $user,
                    meta: [
                        'cart_id' => $cart->id,
                        'reason' => $attempt->failure_reason,
                    ],
                );

                return [
                    'status' => CheckoutAttempt::STATUS_FAILED,
                    'cart' => $cart->fresh(['items']),
                    'attempt' => $attempt,
                    'order' => null,
                    'payment' => null,
                ];
            }

            $order = $this->orders->createOrder($user, $cart->items->map(function ($item) {
                return [
                    'product_sku' => $item->product_sku,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'metadata' => $item->metadata,
                ];
            })->all(), [
                'shipping_cents' => $cart->shipping_cents,
                'tax_cents' => $cart->tax_cents,
                'discount_cents' => $cart->discount_cents,
                'currency' => $cart->currency,
                'metadata' => array_merge($cart->metadata ?? [], ['cart_id' => $cart->id]),
                'initial_status' => Order::STATUS_PAID,
                'status_reason' => 'payment_confirmed',
            ]);

            $transactionId = 'MOCK-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider' => Payment::PROVIDER_MOCK,
                'status' => Payment::STATUS_PAID,
                'amount_cents' => $order->total_cents,
                'currency' => $order->currency,
                'transaction_id' => $transactionId,
                'failure_reason' => null,
                'meta' => isset($paymentPayload['meta']) && is_array($paymentPayload['meta']) ? $paymentPayload['meta'] : null,
                'processed_at' => now(),
            ]);

            $attempt->forceFill([
                'order_id' => $order->id,
            ])->save();

            $this->carts->markAsConverted($cart);

            return [
                'status' => CheckoutAttempt::STATUS_PAID,
                'cart' => $cart->fresh(['items']),
                'attempt' => $attempt->fresh(),
                'order' => $order->fresh(['items', 'statusHistory']),
                'payment' => $payment->fresh(),
            ];
        });
    }

    private function resolveStatus(string $status): string
    {
        $status = trim(strtolower($status));

        if (! in_array($status, [CheckoutAttempt::STATUS_PAID, CheckoutAttempt::STATUS_FAILED], true)) {
            throw new InvalidArgumentException('Statut de paiement mock non supporte.');
        }

        return $status;
    }
}
