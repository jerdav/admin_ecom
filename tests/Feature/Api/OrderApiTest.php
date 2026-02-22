<?php

namespace Tests\Feature\Api;

use App\Models\CheckoutAttempt;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_checkout_paid_creates_order_and_syncs_profile(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        Sanctum::actingAs($customer);

        $this->putJson('/api/cart/items', [
            'items' => [
                [
                    'product_name' => 'Keyboard',
                    'quantity' => 1,
                    'unit_price_cents' => 5000,
                ],
            ],
            'shipping_cents' => 700,
            'tax_cents' => 1000,
            'discount_cents' => 200,
            'phone' => '0601020304',
            'address_line_1' => '1 Main street',
            'postal_code' => '10000',
            'city' => 'Troyes',
            'country' => 'fr',
        ])->assertOk();

        $response = $this->postJson('/api/cart/checkout', [
            'status' => CheckoutAttempt::STATUS_PAID,
        ]);

        $response->assertCreated()->assertJsonPath('order.status', Order::STATUS_PAID);

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'subtotal_cents' => 5000,
            'total_cents' => 6500,
            'status' => Order::STATUS_PAID,
        ]);

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $customer->id,
            'phone' => '0601020304',
            'city' => 'Troyes',
            'country' => 'FR',
        ]);
    }

    public function test_failed_checkout_keeps_cart_and_creates_no_order(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        Sanctum::actingAs($customer);

        $this->putJson('/api/cart/items', [
            'items' => [
                [
                    'product_name' => 'Mouse',
                    'quantity' => 1,
                    'unit_price_cents' => 2500,
                ],
            ],
        ])->assertOk();

        $response = $this->postJson('/api/cart/checkout', [
            'status' => CheckoutAttempt::STATUS_FAILED,
            'failure_reason' => 'declined',
        ]);

        $response->assertStatus(402)
            ->assertJsonPath('attempt.status', CheckoutAttempt::STATUS_FAILED);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('checkout_attempts', [
            'user_id' => $customer->id,
            'status' => CheckoutAttempt::STATUS_FAILED,
            'failure_reason' => 'declined',
        ]);
        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'converted_at' => null,
        ]);
    }

    public function test_customer_cannot_access_another_customer_order(): void
    {
        $customerA = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $customerB = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-000001',
            'user_id' => $customerA->id,
            'status' => Order::STATUS_PAID,
            'currency' => 'EUR',
            'subtotal_cents' => 1000,
            'shipping_cents' => 0,
            'tax_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 1000,
            'customer_email' => $customerA->email,
            'customer_name' => $customerA->name,
            'placed_at' => now(),
        ]);

        Sanctum::actingAs($customerB);

        $this->getJson('/api/orders/'.$order->id)->assertStatus(403);
    }
}
