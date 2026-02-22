<?php

namespace Tests\Feature\Ecommerce;

use App\Models\ShippingProvider;
use App\Models\User;
use App\Services\Ecommerce\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_shipping_is_applied_when_subtotal_reaches_threshold(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $provider = ShippingProvider::query()->create([
            'name' => 'Livraison Test',
            'code' => 'livraison_test',
            'enabled' => true,
            'flat_rate_cents' => 800,
            'free_shipping_threshold_cents' => 5000,
        ]);

        $cart = app(CartService::class)->sync($customer, [
            [
                'product_name' => 'Produit 1',
                'quantity' => 1,
                'unit_price_cents' => 6000,
            ],
        ], [
            'shipping_provider_id' => $provider->id,
        ]);

        $this->assertSame(0, $cart->shipping_cents);
        $this->assertSame(6000, $cart->total_cents);
        $this->assertTrue((bool) data_get($cart->metadata, 'shipping_free_applied'));
    }

    public function test_flat_rate_is_applied_when_threshold_not_reached(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $provider = ShippingProvider::query()->create([
            'name' => 'Livraison Test',
            'code' => 'livraison_test_2',
            'enabled' => true,
            'flat_rate_cents' => 800,
            'free_shipping_threshold_cents' => 5000,
        ]);

        $cart = app(CartService::class)->sync($customer, [
            [
                'product_name' => 'Produit 2',
                'quantity' => 1,
                'unit_price_cents' => 2000,
            ],
        ], [
            'shipping_provider_id' => $provider->id,
        ]);

        $this->assertSame(800, $cart->shipping_cents);
        $this->assertSame(2800, $cart->total_cents);
        $this->assertFalse((bool) data_get($cart->metadata, 'shipping_free_applied'));
    }
}
