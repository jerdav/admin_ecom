<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_orders_index_and_detail(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-900001',
            'user_id' => $customer->id,
            'status' => Order::STATUS_PAID,
            'currency' => 'EUR',
            'subtotal_cents' => 2000,
            'shipping_cents' => 300,
            'tax_cents' => 200,
            'discount_cents' => 0,
            'total_cents' => 2500,
            'customer_email' => $customer->email,
            'customer_name' => $customer->name,
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_sku' => 'SKU-1',
            'product_name' => 'Produit X',
            'quantity' => 1,
            'unit_price_cents' => 2000,
            'total_price_cents' => 2000,
        ]);

        $this->actingAs($admin)->get('/admin/commandes')
            ->assertOk()
            ->assertSee('Commandes')
            ->assertSee($order->order_number);

        $this->actingAs($admin)->get('/admin/commandes/'.$order->id)
            ->assertOk()
            ->assertSee('Detail commande')
            ->assertSee('Produit X');
    }

    public function test_admin_can_update_order_status_from_detail_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-900002',
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

        $this->actingAs($admin)
            ->post('/admin/commandes/'.$order->id.'/status', [
                'status' => Order::STATUS_PAID,
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
    }

    public function test_admin_can_refund_paid_payment_from_detail_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-900003',
            'user_id' => $customer->id,
            'status' => Order::STATUS_PAID,
            'currency' => 'EUR',
            'subtotal_cents' => 1500,
            'shipping_cents' => 0,
            'tax_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 1500,
            'customer_email' => $customer->email,
            'customer_name' => $customer->name,
            'placed_at' => now(),
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => Payment::PROVIDER_MOCK,
            'status' => Payment::STATUS_PAID,
            'amount_cents' => 1500,
            'currency' => 'EUR',
            'transaction_id' => 'MOCK-REFUND-1',
            'processed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/commandes/'.$order->id.'/payments/'.$payment->id.'/refund', [
                'reason' => 'test_refund',
            ])
            ->assertRedirect();

        $payment->refresh();
        $order->refresh();

        $this->assertSame(Payment::STATUS_REFUNDED, $payment->status);
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
    }

    public function test_customer_cannot_access_orders_admin_pages(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)->get('/admin/commandes')->assertForbidden();
    }
}
