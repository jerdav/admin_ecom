<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateCustomerProfileRequest;
use App\Models\User;
use App\Services\Ecommerce\CustomerProfileService;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user()->load('customerProfile');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'customer_profile' => $user->customerProfile,
            ],
        ]);
    }

    public function updateProfile(UpdateCustomerProfileRequest $request, CustomerProfileService $profiles): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $profile = $profiles->syncFromCheckout($user, $request->validated());

        return response()->json([
            'message' => 'Profile updated.',
            'customer_profile' => $profile,
        ]);
    }
}
