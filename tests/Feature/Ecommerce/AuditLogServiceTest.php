<?php

namespace Tests\Feature\Ecommerce;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ecommerce\OrderService;
use App\Services\Ecommerce\PaymentService;
use App\Services\Ecommerce\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_critical_setting_updates_only(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $settings = app(SettingService::class);

        $settings->set('shop.currency', 'EUR', 'string', 'shop', $admin);
        $settings->set('shop.currency', 'USD', 'string', 'shop', $admin);
        $settings->set('shop.name', 'My Shop', 'string', 'shop', $admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.critical_updated',
            'user_id' => $admin->id,
            'entity_type' => 'setting',
        ]);

        $this->assertSame(2, AuditLog::query()->where('action', 'settings.critical_updated')->count());
    }

    public function test_it_logs_order_status_transition_and_refund(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $orderService = app(OrderService::class);
        $paymentService = app(PaymentService::class);

        $order = $orderService->createOrder($customer, [
            [
                'product_name' => 'Product A',
                'quantity' => 1,
                'unit_price_cents' => 1000,
            ],
        ]);

        $orderService->transitionStatus($order, Order::STATUS_PAID, $admin, 'manual_confirm');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'orders.status_changed',
            'entity_type' => 'order',
            'entity_id' => $order->id,
            'user_id' => $admin->id,
        ]);

        $paidPayment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => Payment::PROVIDER_MOCK,
            'status' => Payment::STATUS_PAID,
            'amount_cents' => $order->total_cents,
            'currency' => $order->currency,
            'transaction_id' => 'MOCK-TEST-000001',
            'processed_at' => now(),
        ]);

        $paymentService->refundPayment($paidPayment, $admin, ['reason' => 'customer_request']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payments.refunded',
            'entity_type' => 'payment',
            'entity_id' => $paidPayment->id,
            'user_id' => $admin->id,
        ]);
    }
}
