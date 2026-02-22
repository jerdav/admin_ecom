<?php

namespace Tests\Feature;

use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShippingProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_shipping_provider(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post('/admin/parametres/transporteurs', [
                'name' => 'Colis Expert',
                'code' => 'colis_expert',
                'enabled' => 1,
                'flat_rate_eur' => '6.90',
                'free_shipping_threshold_eur' => '100.00',
            ])
            ->assertRedirect();

        $provider = ShippingProvider::query()->where('code', 'colis_expert')->first();
        $this->assertNotNull($provider);

        $this->actingAs($admin)
            ->post('/admin/parametres/transporteurs/'.$provider->id, [
                'name' => 'Colis Expert Plus',
                'code' => 'colis_expert',
                'enabled' => 1,
                'flat_rate_eur' => '7.90',
                'free_shipping_threshold_eur' => '120.00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shipping_providers', [
            'id' => $provider->id,
            'name' => 'Colis Expert Plus',
            'flat_rate_cents' => 790,
            'free_shipping_threshold_cents' => 12000,
        ]);

        $this->actingAs($admin)
            ->post('/admin/parametres/transporteurs/'.$provider->id.'/delete')
            ->assertRedirect();

        $this->assertDatabaseMissing('shipping_providers', [
            'id' => $provider->id,
        ]);
    }
}
