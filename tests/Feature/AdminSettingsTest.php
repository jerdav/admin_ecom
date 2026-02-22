<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get('/admin/parametres')
            ->assertOk()
            ->assertSee('Parametres')
            ->assertSee('Transporteurs');
    }

    public function test_admin_can_update_settings_and_flags(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post('/admin/parametres', [
                'shop_name' => 'Boutique Test',
                'shop_currency' => 'usd',
                'tax_default_rate' => 18,
                'orders_auto_confirm' => 1,
                'mail_order_notifications' => 1,
                'users_allow_secondary_users' => 0,
                'users_default_role' => 'customer',
                'feature_flags' => [
                    'payment.mock' => 1,
                    'payment.stripe' => 1,
                    'payment.paypal' => 0,
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'shop.name', 'value' => 'Boutique Test']);
        $this->assertDatabaseHas('settings', ['key' => 'shop.currency', 'value' => 'USD']);
        $this->assertDatabaseHas('settings', ['key' => 'tax.default_rate', 'value' => '18']);
        $this->assertDatabaseHas('settings', ['key' => 'orders.auto_confirm', 'value' => '1']);

        $this->assertDatabaseHas('feature_flags', ['code' => 'payment.stripe', 'enabled' => 1]);
        $this->assertDatabaseHas('feature_flags', ['code' => 'payment.paypal', 'enabled' => 0]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.critical_updated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_customer_cannot_access_settings_page(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get('/admin/parametres')
            ->assertForbidden();
    }
}
