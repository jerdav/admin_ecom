<?php

namespace App\Services\Ecommerce;

use App\Models\ShippingProvider;
use InvalidArgumentException;

class ShippingService
{
    /**
     * @return array{provider: ShippingProvider, shipping_cents: int, applied_free_shipping: bool}
     */
    public function quote(int $subtotalCents, int $providerId): array
    {
        if ($subtotalCents < 0) {
            throw new InvalidArgumentException('Le sous-total ne peut pas etre negatif.');
        }

        $provider = ShippingProvider::query()->find($providerId);

        if (! $provider || ! $provider->enabled) {
            throw new InvalidArgumentException('Transporteur invalide ou inactif.');
        }

        $threshold = $provider->free_shipping_threshold_cents;
        $appliedFreeShipping = $threshold !== null && $threshold >= 0 && $subtotalCents >= $threshold;

        $shippingCents = $appliedFreeShipping ? 0 : max(0, (int) $provider->flat_rate_cents);

        return [
            'provider' => $provider,
            'shipping_cents' => $shippingCents,
            'applied_free_shipping' => $appliedFreeShipping,
        ];
    }
}
