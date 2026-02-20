<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Order;
use App\Models\User;
use App\Services\Ecommerce\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_with_totals_and_initial_history(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $order = app(OrderService::class)->createOrder($user, [
            [
                'product_sku' => 'SKU-001',
                'product_name' => 'T-Shirt',
                'quantity' => 2,
                'unit_price_cents' => 1500,
            ],
            [
                'product_sku' => 'SKU-002',
                'product_name' => 'Mug',
                'quantity' => 1,
                'unit_price_cents' => 800,
            ],
        ], [
            'shipping_cents' => 500,
            'tax_cents' => 400,
            'discount_cents' => 300,
            'currency' => 'eur',
        ]);

        $this->assertSame(3800, $order->subtotal_cents);
        $this->assertSame(4400, $order->total_cents);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertCount(2, $order->items);
        $this->assertCount(1, $order->statusHistory);
        $this->assertSame(Order::STATUS_PENDING, $order->statusHistory->first()->to_status);
    }

    public function test_it_transitions_status_and_rejects_invalid_transition(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $service = app(OrderService::class);

        $order = $service->createOrder($user, [
            [
                'product_name' => 'Product A',
                'quantity' => 1,
                'unit_price_cents' => 1000,
            ],
        ]);

        $order = $service->transitionStatus($order, Order::STATUS_PAID, $admin, 'payment_confirmed');
        $this->assertSame(Order::STATUS_PAID, $order->status);

        $this->expectException(InvalidArgumentException::class);
        $service->transitionStatus($order, Order::STATUS_DELIVERED, $admin, 'skip_shipping');
    }
}
