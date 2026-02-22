<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShippingProviderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:shipping_providers,code'],
            'enabled' => ['nullable', 'boolean'],
            'flat_rate_eur' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold_eur' => ['nullable', 'numeric', 'min:0'],
        ]);

        ShippingProvider::query()->create([
            'name' => trim($validated['name']),
            'code' => strtolower($validated['code']),
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'flat_rate_cents' => $this->eurToCents($validated['flat_rate_eur']),
            'free_shipping_threshold_cents' => $this->nullableEurToCents($validated['free_shipping_threshold_eur'] ?? null),
            'config' => null,
        ]);

        return back()->with('success', 'Transporteur ajoute.');
    }

    public function update(Request $request, ShippingProvider $provider): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('shipping_providers', 'code')->ignore($provider->id)],
            'enabled' => ['nullable', 'boolean'],
            'flat_rate_eur' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold_eur' => ['nullable', 'numeric', 'min:0'],
        ]);

        $provider->forceFill([
            'name' => trim($validated['name']),
            'code' => strtolower($validated['code']),
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'flat_rate_cents' => $this->eurToCents($validated['flat_rate_eur']),
            'free_shipping_threshold_cents' => $this->nullableEurToCents($validated['free_shipping_threshold_eur'] ?? null),
        ])->save();

        return back()->with('success', 'Transporteur mis a jour.');
    }

    public function destroy(ShippingProvider $provider): RedirectResponse
    {
        $provider->delete();

        return back()->with('success', 'Transporteur supprime.');
    }

    private function eurToCents(mixed $value): int
    {
        return (int) round($this->normalizeDecimal($value) * 100);
    }

    private function nullableEurToCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->eurToCents($value);
    }

    private function normalizeDecimal(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return (float) $value;
    }
}
