<?php

namespace App\Services\Ecommerce;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartService
{
    public function __construct(
        private readonly ShippingService $shipping,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $context
     */
    public function sync(User $user, array $items, array $context = []): Cart
    {
        if ($items === []) {
            throw new InvalidArgumentException('Le panier ne peut pas etre vide.');
        }

        [$normalizedItems, $subtotal] = $this->normalizeItems($items);

        $shipping = $this->intValue($context['shipping_cents'] ?? 0);

        if (isset($context['shipping_provider_id']) && $context['shipping_provider_id'] !== null && $context['shipping_provider_id'] !== '') {
            $quote = $this->shipping->quote($subtotal, (int) $context['shipping_provider_id']);
            $shipping = $quote['shipping_cents'];

            $context['metadata'] = array_merge((array) ($context['metadata'] ?? []), [
                'shipping_provider_id' => $quote['provider']->id,
                'shipping_provider_code' => $quote['provider']->code,
                'shipping_provider_name' => $quote['provider']->name,
                'shipping_free_applied' => $quote['applied_free_shipping'],
            ]);
        }

        $tax = $this->intValue($context['tax_cents'] ?? 0);
        $discount = $this->intValue($context['discount_cents'] ?? 0);
        $total = max(0, $subtotal + $shipping + $tax - $discount);

        return DB::transaction(function () use ($user, $context, $normalizedItems, $subtotal, $shipping, $tax, $discount, $total) {
            $cart = $this->getOrCreateActiveCart($user);

            $cart->forceFill([
                'currency' => strtoupper((string) ($context['currency'] ?? config('ecommerce.currency', 'EUR'))),
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $shipping,
                'tax_cents' => $tax,
                'discount_cents' => $discount,
                'total_cents' => $total,
                'metadata' => isset($context['metadata']) && is_array($context['metadata']) ? $context['metadata'] : null,
            ])->save();

            $cart->items()->delete();
            $cart->items()->createMany($normalizedItems);

            return $cart->fresh(['items']);
        });
    }

    public function activeCart(User $user): ?Cart
    {
        return Cart::query()
            ->with('items')
            ->where('user_id', $user->id)
            ->whereNull('converted_at')
            ->latest('id')
            ->first();
    }

    public function markAsConverted(Cart $cart): Cart
    {
        $cart->forceFill([
            'converted_at' => now(),
        ])->save();

        return $cart->fresh(['items']);
    }

    private function getOrCreateActiveCart(User $user): Cart
    {
        $existing = $this->activeCart($user);

        if ($existing) {
            return $existing;
        }

        return Cart::query()->create([
            'user_id' => $user->id,
            'currency' => strtoupper((string) config('ecommerce.currency', 'EUR')),
            'subtotal_cents' => 0,
            'shipping_cents' => 0,
            'tax_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 0,
        ]);
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
                throw new InvalidArgumentException("Article index {$index} sans product_name.");
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException("La quantite de {$name} doit etre > 0.");
            }

            if ($unitPrice < 0) {
                throw new InvalidArgumentException("Le prix unitaire de {$name} doit etre >= 0.");
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
            throw new InvalidArgumentException('Les montants ne peuvent pas etre negatifs.');
        }

        return $number;
    }
}
