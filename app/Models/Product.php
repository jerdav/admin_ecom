<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'main_image_url',
        'price_cents',
        'tax_rate',
        'stock_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'int',
            'price_cents' => 'int',
            'tax_rate' => 'int',
            'stock_quantity' => 'int',
            'is_active' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
}
