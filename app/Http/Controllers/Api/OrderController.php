<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Ecommerce\CustomerProfileService;
use App\Services\Ecommerce\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $query = Order::query()
            ->with('items')
            ->latest('id');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return response()->json([
            'data' => $query->paginate(20),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $order->load(['items', 'statusHistory', 'payments']);

        return response()->json([
            'data' => $order,
        ]);
    }

    public function store(StoreOrderRequest $request, OrderService $orders, CustomerProfileService $profiles): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Admin cannot place customer orders from this endpoint.',
            ], 403);
        }

        $validated = $request->validated();

        $order = $orders->createOrder(
            $user,
            $validated['items'],
            [
                'shipping_cents' => $validated['shipping_cents'] ?? 0,
                'tax_cents' => $validated['tax_cents'] ?? 0,
                'discount_cents' => $validated['discount_cents'] ?? 0,
                'currency' => $validated['currency'] ?? config('ecommerce.currency', 'EUR'),
                'metadata' => $validated['metadata'] ?? null,
            ]
        );

        $profiles->syncFromCheckout($user, [
            'phone' => $validated['phone'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
        ]);

        return response()->json([
            'message' => 'Order created.',
            'data' => $order->load(['items', 'statusHistory']),
        ], 201);
    }
}
