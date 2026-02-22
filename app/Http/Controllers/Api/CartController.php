<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutCartRequest;
use App\Http\Requests\Api\StoreCartRequest;
use App\Models\User;
use App\Services\Ecommerce\CartService;
use App\Services\Ecommerce\CheckoutService;
use App\Services\Ecommerce\CustomerProfileService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CartController extends Controller
{
    public function show(CartService $carts): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Endpoint reserve aux clients.',
            ], 403);
        }

        return response()->json([
            'data' => $carts->activeCart($user),
        ]);
    }

    public function sync(StoreCartRequest $request, CartService $carts, CustomerProfileService $profiles): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Endpoint reserve aux clients.',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $cart = $carts->sync($user, $validated['items'], [
                'shipping_provider_id' => $validated['shipping_provider_id'] ?? null,
                'shipping_cents' => $validated['shipping_cents'] ?? 0,
                'tax_cents' => $validated['tax_cents'] ?? 0,
                'discount_cents' => $validated['discount_cents'] ?? 0,
                'currency' => $validated['currency'] ?? config('ecommerce.currency', 'EUR'),
                'metadata' => $validated['metadata'] ?? null,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

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
            'message' => 'Panier mis a jour.',
            'data' => $cart,
        ]);
    }

    public function checkout(CheckoutCartRequest $request, CheckoutService $checkout): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Endpoint reserve aux clients.',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $result = $checkout->processMockCheckout(
                $user,
                [
                    'status' => $validated['status'] ?? null,
                    'failure_reason' => $validated['failure_reason'] ?? null,
                    'meta' => $validated['meta'] ?? null,
                ],
                [
                    'phone' => $validated['phone'] ?? null,
                    'address_line_1' => $validated['address_line_1'] ?? null,
                    'address_line_2' => $validated['address_line_2'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'country' => $validated['country'] ?? null,
                ],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($result['status'] === 'failed') {
            return response()->json([
                'message' => 'Paiement echoue, panier conserve.',
                'attempt' => $result['attempt'],
                'cart' => $result['cart'],
            ], 402);
        }

        return response()->json([
            'message' => 'Paiement valide, commande creee.',
            'attempt' => $result['attempt'],
            'order' => $result['order'],
            'payment' => $result['payment'],
        ], 201);
    }
}
