<?php

namespace Tests\Feature\Ecommerce;

use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Models\User;
use App\Services\Ecommerce\CartService;
use App\Services\Ecommerce\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_checkout_converts_cart_and_creates_paid_order(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $cart = app(CartService::class)->sync($customer, [
            [
                'product_name' => 'Produit 1',
                'quantity' => 2,
                'unit_price_cents' => 1000,
            ],
        ], [
            'shipping_cents' => 300,
            'tax_cents' => 200,
        ]);

        $result = app(CheckoutService::class)->processMockCheckout($customer, [
            'status' => CheckoutAttempt::STATUS_PAID,
        ]);

        $this->assertSame(CheckoutAttempt::STATUS_PAID, $result['status']);
        $this->assertNotNull($result['order']);
        $this->assertSame(Order::STATUS_PAID, $result['order']->status);

        $cart->refresh();
        $this->assertNotNull($cart->converted_at);
    }

    public function test_failed_checkout_keeps_cart_unconverted(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $cart = app(CartService::class)->sync($customer, [
            [
                'product_name' => 'Produit 2',
                'quantity' => 1,
                'unit_price_cents' => 2000,
            ],
        ]);

        $result = app(CheckoutService::class)->processMockCheckout($customer, [
            'status' => CheckoutAttempt::STATUS_FAILED,
            'failure_reason' => 'mock_declined',
        ]);

        $this->assertSame(CheckoutAttempt::STATUS_FAILED, $result['status']);
        $this->assertNull($result['order']);

        $cart->refresh();
        $this->assertNull($cart->converted_at);
    }
}
