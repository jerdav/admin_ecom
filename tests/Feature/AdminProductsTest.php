<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_product(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::query()->create([
            'name' => 'Vetements',
            'slug' => 'vetements',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/catalogue/produits', [
                'name' => 'T-shirt Bleu',
                'sku' => null,
                'category_id' => $category->id,
                'description' => 'Coton 100%',
                'main_image_file' => UploadedFile::fake()->image('main.jpg'),
                'gallery_files' => [
                    UploadedFile::fake()->image('galerie-1.jpg'),
                    UploadedFile::fake()->image('galerie-2.jpg'),
                ],
                'price_eur' => '24.90',
                'tax_rate' => 20,
                'stock_quantity' => 12,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $product = Product::query()->where('slug', 't-shirt-bleu')->first();
        $this->assertNotNull($product);
        $this->assertNull($product->sku);

        $this->actingAs($admin)
            ->post('/admin/catalogue/produits/'.$product->id, [
                'name' => 'T-shirt Bleu Premium',
                'sku' => null,
                'category_id' => $category->id,
                'description' => 'Coton premium',
                'main_image_file' => UploadedFile::fake()->image('main-new.jpg'),
                'gallery_files' => [
                    UploadedFile::fake()->image('galerie-3.jpg'),
                ],
                'price_eur' => '29,90',
                'tax_rate' => 20,
                'stock_quantity' => 8,
                'is_active' => 0,
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertNotNull($product->main_image_url);
        Storage::disk('public')->assertExists($product->main_image_url);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'T-shirt Bleu Premium',
            'slug' => 't-shirt-bleu-premium',
            'sku' => null,
            'price_cents' => 2990,
            'stock_quantity' => 8,
            'is_active' => 0,
        ]);

        $this->assertDatabaseCount('product_images', 3);

        $imageToDelete = $product->images()->first();
        $this->assertNotNull($imageToDelete);
        $imagePath = $imageToDelete->image_url;
        $this->actingAs($admin)
            ->post('/admin/catalogue/produits/'.$product->id.'/images/'.$imageToDelete->id.'/delete')
            ->assertRedirect();

        $this->assertDatabaseMissing('product_images', ['id' => $imageToDelete->id]);
        Storage::disk('public')->assertMissing($imagePath);

        $this->actingAs($admin)
            ->post('/admin/catalogue/produits/'.$product->id.'/delete')
            ->assertRedirect();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_customer_cannot_access_products_admin_page(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get('/admin/catalogue/produits')
            ->assertForbidden();
    }
}
