<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->withCount('products')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function edit(Category $category)
    {
        $category->loadCount('products');

        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::query()->create([
            'name' => trim($validated['name']),
            'slug' => $this->generateUniqueSlug((string) $validated['name']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('admin.categories')
            ->with('success', 'Categorie ajoutee.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->forceFill([
            'name' => trim($validated['name']),
            'slug' => $this->generateUniqueSlug((string) $validated['name'], $category->id),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        return redirect()->route('admin.categories')
            ->with('success', 'Categorie mise a jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories')
            ->with('success', 'Categorie supprimee.');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'categorie';
        }

        $candidate = $baseSlug;
        $index = 2;

        while ($this->slugExists($candidate, $ignoreCategoryId)) {
            $candidate = $baseSlug.'-'.$index;
            $index++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, ?int $ignoreCategoryId = null): bool
    {
        return Category::query()
            ->when($ignoreCategoryId !== null, fn ($query) => $query->where('id', '!=', $ignoreCategoryId))
            ->where('slug', $slug)
            ->exists();
    }
}

