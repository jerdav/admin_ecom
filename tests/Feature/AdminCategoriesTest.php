<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_category(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post('/admin/catalogue/categories', [
                'name' => 'Maison',
                'is_active' => 1,
            ])
            ->assertRedirect('/admin/catalogue/categories');

        $category = Category::query()->where('slug', 'maison')->first();
        $this->assertNotNull($category);

        $this->actingAs($admin)
            ->post('/admin/catalogue/categories/'.$category->id, [
                'name' => 'Maison Deco',
                'is_active' => 0,
            ])
            ->assertRedirect('/admin/catalogue/categories');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Maison Deco',
            'slug' => 'maison-deco',
            'is_active' => 0,
        ]);

        $this->actingAs($admin)
            ->post('/admin/catalogue/categories/'.$category->id.'/delete')
            ->assertRedirect('/admin/catalogue/categories');

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_customer_cannot_access_categories_admin_pages(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get('/admin/catalogue/categories')
            ->assertForbidden();
    }
}

