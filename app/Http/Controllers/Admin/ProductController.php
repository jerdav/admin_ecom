<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index', [
            'products' => Product::query()->with(['category', 'images'])->orderByDesc('id')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('admin.products.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Product $product)
    {
        $product->load(['category', 'images']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:products,sku'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'main_image_file' => ['nullable', 'image', 'max:5120'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['image', 'max:5120'],
            'price_eur' => ['required', 'string', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'tax_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = Product::query()->create([
            'name' => trim($validated['name']),
            'slug' => $this->generateUniqueSlug((string) $validated['name']),
            'sku' => isset($validated['sku']) && $validated['sku'] !== '' ? strtoupper((string) $validated['sku']) : null,
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'main_image_url' => null,
            'price_cents' => $this->eurToCents($validated['price_eur']),
            'tax_rate' => (int) $validated['tax_rate'],
            'stock_quantity' => (int) $validated['stock_quantity'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $this->storeMainImage($request, $product);
        $this->appendGalleryImages($request, $product);

        return redirect()->route('admin.products')
            ->with('success', 'Produit ajoute.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('products', 'sku')->ignore($product->id)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'main_image_file' => ['nullable', 'image', 'max:5120'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['image', 'max:5120'],
            'price_eur' => ['required', 'string', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'tax_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->forceFill([
            'name' => trim($validated['name']),
            'slug' => $this->generateUniqueSlug((string) $validated['name'], $product->id),
            'sku' => isset($validated['sku']) && $validated['sku'] !== '' ? strtoupper((string) $validated['sku']) : null,
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'price_cents' => $this->eurToCents($validated['price_eur']),
            'tax_rate' => (int) $validated['tax_rate'],
            'stock_quantity' => (int) $validated['stock_quantity'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        $this->storeMainImage($request, $product);
        $this->appendGalleryImages($request, $product);

        return redirect()->route('admin.products')
            ->with('success', 'Produit mis a jour.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        if ($image->product_id !== $product->id) {
            abort(404);
        }

        $this->deleteStoredFile($image->image_url);
        $image->delete();

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Image de galerie supprimee.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->deleteStoredFile($product->main_image_url);

        foreach ($product->images as $image) {
            $this->deleteStoredFile($image->image_url);
        }

        $product->delete();

        return redirect()->route('admin.products')
            ->with('success', 'Produit supprime.');
    }

    private function eurToCents(mixed $value): int
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return (int) round(((float) $value) * 100);
    }

    private function storeMainImage(Request $request, Product $product): void
    {
        if (! $request->hasFile('main_image_file')) {
            return;
        }

        $path = $request->file('main_image_file')->store('products/main', 'public');
        $this->deleteStoredFile($product->main_image_url);

        $product->forceFill([
            'main_image_url' => $path,
        ])->save();
    }

    private function appendGalleryImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('gallery_files')) {
            return;
        }

        $nextOrder = (int) $product->images()->max('sort_order');
        $nextOrder = $nextOrder + 1;

        foreach ($request->file('gallery_files', []) as $file) {
            $path = $file->store('products/gallery', 'public');
            $product->images()->create([
                'image_url' => $path,
                'alt_text' => $product->name,
                'sort_order' => $nextOrder,
            ]);
            $nextOrder++;
        }
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function generateUniqueSlug(string $name, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'produit';
        }

        $candidate = $baseSlug;
        $index = 2;

        while ($this->slugExists($candidate, $ignoreProductId)) {
            $candidate = $baseSlug.'-'.$index;
            $index++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, ?int $ignoreProductId = null): bool
    {
        return Product::query()
            ->when($ignoreProductId !== null, fn ($query) => $query->where('id', '!=', $ignoreProductId))
            ->where('slug', $slug)
            ->exists();
    }
}
