<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'category_id', 'brand_id', 'slug', 'sku', 'name', 'variant', 'description',
    'price', 'old_price', 'cost_price', 'stock', 'low_stock_threshold',
    'rating', 'reviews', 'kind', 'c1', 'c2', 'label', 'image',
    'is_new', 'is_hot', 'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Packaging shapes drawn by the storefront product-art component.
     *
     * @var list<string>
     */
    public const KINDS = ['bottle', 'pack', 'tube', 'box', 'soap'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'rating' => 'decimal:1',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'reviews' => 'integer',
            'is_new' => 'boolean',
            'is_hot' => 'boolean',
            'is_active' => 'boolean',
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
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    /**
     * Discount percentage derived from the crossed-out price.
     *
     * @return Attribute<int, never>
     */
    protected function discountPercent(): Attribute
    {
        return Attribute::get(function (): int {
            if (! $this->old_price || (float) $this->old_price <= (float) $this->price) {
                return 0;
            }

            return (int) round((1 - (float) $this->price / (float) $this->old_price) * 100);
        });
    }

    /**
     * Public URL of the product photo: uploaded file first, then the bundled image.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->image) {
                return Storage::disk('public')->url($this->image);
            }

            if (is_file(public_path("img/products/{$this->slug}.jpg"))) {
                return asset("img/products/{$this->slug}.jpg");
            }

            return null;
        });
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    /**
     * Shape expected by the storefront views and scripts.
     *
     * @return array<string, mixed>
     */
    public function toCatalogArray(): array
    {
        $catalog = [
            'slug' => $this->slug,
            'name' => $this->name,
            'variant' => $this->variant,
            'brand' => $this->brand?->name ?? '',
            'category' => $this->category?->slug ?? '',
            'price' => (float) $this->price,
            'old_price' => $this->old_price !== null ? (float) $this->old_price : null,
            'off' => $this->discount_percent,
            'rating' => (float) ($this->rating ?? 0),
            'reviews' => $this->reviews,
            'kind' => $this->kind,
            'c1' => $this->c1,
            'c2' => $this->c2,
            'label' => $this->label ?? $this->name,
            'is_new' => $this->is_new,
            'hot' => $this->is_hot,
            'stock' => $this->stock,
        ];

        if ($url = $this->image_url) {
            $catalog['image'] = $url;
        }

        return $catalog;
    }
}
