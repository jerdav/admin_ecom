<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ecommerce\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_process_mock_payment(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $order = app(OrderService::class)->createOrder($customer, [
            [
                'product_name' => 'Product Z',
                'quantity' => 1,
                'unit_price_cents' => 1500,
            ],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/orders/'.$order->id.'/payments/mock', [
            'status' => Payment::STATUS_PAID,
        ]);

        $response->assertCreated()->assertJsonPath('data.status', Payment::STATUS_PAID);

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
    }

    public function test_customer_cannot_access_admin_payment_endpoints(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-000002',
            'user_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'currency' => 'EUR',
            'subtotal_cents' => 1000,
            'shipping_cents' => 0,
            'tax_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 1000,
            'customer_email' => $customer->email,
            'customer_name' => $customer->name,
            'placed_at' => now(),
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/admin/orders/'.$order->id.'/payments/mock', [
            'status' => Payment::STATUS_PAID,
        ])->assertStatus(403);
    }
}
