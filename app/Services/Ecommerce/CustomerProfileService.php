<?php

namespace App\Services\Ecommerce;

use App\Models\CustomerProfile;
use App\Models\User;

class CustomerProfileService
{
    /**
     * Synchronize customer profile data after checkout.
     *
     * @param array<string, mixed> $checkoutData
     */
    public function syncFromCheckout(User $user, array $checkoutData): CustomerProfile
    {
        if (! $user->isAdmin() && $user->role !== User::ROLE_CUSTOMER) {
            $user->forceFill(['role' => User::ROLE_CUSTOMER])->save();
        }

        $payload = [
            'phone' => $this->stringOrNull($checkoutData['phone'] ?? null),
            'address_line_1' => $this->stringOrNull($checkoutData['address_line_1'] ?? null),
            'address_line_2' => $this->stringOrNull($checkoutData['address_line_2'] ?? null),
            'postal_code' => $this->stringOrNull($checkoutData['postal_code'] ?? null),
            'city' => $this->stringOrNull($checkoutData['city'] ?? null),
            'state' => $this->stringOrNull($checkoutData['state'] ?? null),
            'country' => $this->countryOrNull($checkoutData['country'] ?? null),
        ];

        $payload = array_filter($payload, static fn (mixed $value): bool => $value !== null);

        return $user->customerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function countryOrNull(mixed $value): ?string
    {
        $country = $this->stringOrNull($value);

        if ($country === null) {
            return null;
        }

        return strtoupper(substr($country, 0, 2));
    }
}
