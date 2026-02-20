<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ecommerce\OrderService;
use App\Services\Ecommerce\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_mock_payment_and_marks_order_as_paid(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $order = app(OrderService::class)->createOrder($customer, [
            [
                'product_name' => 'Product A',
                'quantity' => 1,
                'unit_price_cents' => 2000,
            ],
        ]);

        $payment = app(PaymentService::class)->createMockPayment($order, [
            'status' => Payment::STATUS_PAID,
        ]);

        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->transaction_id);

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
    }

    public function test_it_records_failed_payment_without_marking_order_paid(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $order = app(OrderService::class)->createOrder($customer, [
            [
                'product_name' => 'Product B',
                'quantity' => 1,
                'unit_price_cents' => 3500,
            ],
        ]);

        $payment = app(PaymentService::class)->createMockPayment($order, [
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => 'insufficient_funds',
        ]);

        $this->assertSame(Payment::STATUS_FAILED, $payment->status);
        $this->assertSame('insufficient_funds', $payment->failure_reason);

        $order->refresh();
        $this->assertSame(Order::STATUS_PENDING, $order->status);
    }

    public function test_it_refunds_paid_payment_and_marks_order_refunded(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $orderService = app(OrderService::class);
        $paymentService = app(PaymentService::class);

        $order = $orderService->createOrder($customer, [
            [
                'product_name' => 'Product C',
                'quantity' => 1,
                'unit_price_cents' => 5000,
            ],
        ]);

        $payment = $paymentService->createMockPayment($order, [
            'status' => Payment::STATUS_PAID,
        ]);

        $payment = $paymentService->refundPayment($payment, $admin, [
            'reason' => 'customer_request',
        ]);

        $this->assertSame(Payment::STATUS_REFUNDED, $payment->status);

        $order->refresh();
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
    }

    public function test_it_rejects_refund_on_non_paid_payment(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $order = app(OrderService::class)->createOrder($customer, [
            [
                'product_name' => 'Product D',
                'quantity' => 1,
                'unit_price_cents' => 1200,
            ],
        ]);

        $payment = app(PaymentService::class)->createMockPayment($order, [
            'status' => Payment::STATUS_FAILED,
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(PaymentService::class)->refundPayment($payment);
    }
}
