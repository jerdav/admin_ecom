<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
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
                'message' => 'Acces interdit.',
            ], 403);
        }

        $order->load(['items', 'statusHistory', 'payments']);

        return response()->json([
            'data' => $order,
        ]);
    }
}
