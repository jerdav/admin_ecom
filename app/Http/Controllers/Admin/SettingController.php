<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ecommerce\FeatureFlagService;
use App\Services\Ecommerce\SettingService;
use App\Models\ShippingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * @var list<string>
     */
    private const TABS = [
        'boutique',
        'metier',
        'modules',
        'transporteurs',
    ];

    /**
     * @var list<string>
     */
    private const SUPPORTED_FLAGS = [
        'payment.mock',
        'payment.stripe',
        'payment.paypal',
    ];

    public function edit(Request $request, SettingService $settings, FeatureFlagService $flags)
    {
        $availableFlags = collect(self::SUPPORTED_FLAGS)->values();
        $activeTab = (string) $request->query('tab', 'boutique');

        if (! in_array($activeTab, self::TABS, true)) {
            $activeTab = 'boutique';
        }

        return view('admin.settings.index', [
            'activeTab' => $activeTab,
            'form' => [
                'shop_name' => (string) $settings->get('shop.name', config('app.name', 'EcomAdmin')),
                'shop_currency' => (string) $settings->get('shop.currency', config('ecommerce.currency', 'EUR')),
                'tax_default_rate' => (int) $settings->get('tax.default_rate', (int) config('ecommerce.tax.default_rate', 20)),
                'orders_auto_confirm' => (bool) $settings->get('orders.auto_confirm', (bool) config('ecommerce.orders.auto_confirm', false)),
                'mail_order_notifications' => (bool) $settings->get('mail.order_notifications', (bool) config('ecommerce.mail.order_notifications', true)),
                'users_allow_secondary_users' => (bool) $settings->get('users.allow_secondary_users', (bool) config('ecommerce.users.allow_secondary_users', false)),
                'users_default_role' => (string) $settings->get('users.default_role', (string) config('ecommerce.users.default_role', 'customer')),
            ],
            'flags' => $availableFlags->map(function (string $code) use ($flags) {
                return [
                    'code' => $code,
                    'label' => $this->featureFlagLabel($code),
                    'enabled' => $flags->isEnabled($code, 'global', false),
                ];
            }),
            'shippingProviders' => ShippingProvider::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SettingService $settings, FeatureFlagService $flags): RedirectResponse
    {
        $section = (string) $request->input('section', 'all');

        $actor = $request->user();

        if ($section === 'boutique' || $section === 'all') {
            $validated = $request->validate([
                'shop_name' => ['required', 'string', 'max:120'],
                'shop_currency' => ['required', 'string', 'size:3'],
            ]);

            $settings->set('shop.name', trim($validated['shop_name']), 'string', 'shop', $actor);
            $settings->set('shop.currency', strtoupper($validated['shop_currency']), 'string', 'shop', $actor);
        }

        if ($section === 'metier' || $section === 'all') {
            $validated = $request->validate([
                'tax_default_rate' => ['required', 'integer', 'min:0', 'max:100'],
                'orders_auto_confirm' => ['nullable', 'boolean'],
                'mail_order_notifications' => ['nullable', 'boolean'],
                'users_allow_secondary_users' => ['nullable', 'boolean'],
                'users_default_role' => ['required', 'string', Rule::in(['customer', 'admin'])],
            ]);

            $settings->set('tax.default_rate', (int) $validated['tax_default_rate'], 'int', 'tax', $actor);
            $settings->set('orders.auto_confirm', (bool) ($validated['orders_auto_confirm'] ?? false), 'bool', 'orders', $actor);
            $settings->set('mail.order_notifications', (bool) ($validated['mail_order_notifications'] ?? false), 'bool', 'mail', $actor);
            $settings->set('users.allow_secondary_users', (bool) ($validated['users_allow_secondary_users'] ?? false), 'bool', 'users', $actor);
            $settings->set('users.default_role', $validated['users_default_role'], 'string', 'users', $actor);
        }

        if ($section === 'modules' || $section === 'all') {
            $validated = $request->validate([
                'feature_flags' => ['nullable', 'array'],
                'feature_flags.*' => ['nullable', 'boolean'],
            ]);

            $inputFlags = (array) ($validated['feature_flags'] ?? []);

            foreach (self::SUPPORTED_FLAGS as $flagCode) {
                $flags->set($flagCode, (bool) ($inputFlags[$flagCode] ?? false), 'global');
            }
        }

        $tabBySection = [
            'boutique' => 'boutique',
            'metier' => 'metier',
            'modules' => 'modules',
            'all' => 'boutique',
        ];
        $redirectTab = $tabBySection[$section] ?? 'boutique';

        return redirect()->route('admin.settings', ['tab' => $redirectTab])
            ->with('success', 'Parametres enregistres avec succes.');
    }

    private function featureFlagLabel(string $code): string
    {
        $labels = [
            'payment.mock' => 'Paiement simule (test)',
            'payment.stripe' => 'Paiement Stripe',
            'payment.paypal' => 'Paiement PayPal',
        ];

        if (isset($labels[$code])) {
            return $labels[$code];
        }

        return ucfirst(str_replace(['.', '_'], ' ', $code));
    }
}
