<?php

namespace Tests\Feature\Ecommerce;

use App\Models\CustomerProfile;
use App\Models\User;
use App\Services\Ecommerce\CustomerProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_customer_profile_from_first_checkout(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $profile = app(CustomerProfileService::class)->syncFromCheckout($user, [
            'phone' => ' 0611223344 ',
            'address_line_1' => '10 Rue Victor Hugo',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'france',
        ]);

        $this->assertInstanceOf(CustomerProfile::class, $profile);
        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
            'phone' => '0611223344',
            'address_line_1' => '10 Rue Victor Hugo',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);
    }

    public function test_it_updates_existing_profile_without_erasing_missing_fields(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $user->customerProfile()->create([
            'phone' => '0600000000',
            'address_line_1' => 'Ancienne adresse',
            'postal_code' => '10000',
            'city' => 'Troyes',
            'country' => 'FR',
        ]);

        app(CustomerProfileService::class)->syncFromCheckout($user, [
            'phone' => '0700000000',
            'city' => 'Lyon',
        ]);

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
            'phone' => '0700000000',
            'address_line_1' => 'Ancienne adresse',
            'postal_code' => '10000',
            'city' => 'Lyon',
            'country' => 'FR',
        ]);
    }
}
