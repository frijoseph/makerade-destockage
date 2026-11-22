<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_id', 'name', 'option1', 'option2', 'option3', 'sku', 'price', 'old_price', 'stock', 'image', 'is_active', 'position'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Label built from the option values (« 500 ml / Rouge »).
     *
     * @param  list<string|null>  $options
     */
    public static function labelFor(array $options): string
    {
        return collect($options)->map(fn ($option): string => trim((string) $option))->filter()->implode(' / ');
    }

    /**
     * Selling price: the variant's own price, otherwise the product's.
     */
    public function effectivePrice(): float
    {
        return (float) ($this->price ?? $this->product->price);
    }

    public function effectiveOldPrice(): ?float
    {
        $old = $this->price !== null ? $this->old_price : ($this->old_price ?? $this->product->old_price);

        return $old !== null && (float) $old > $this->effectivePrice() ? (float) $old : null;
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }
}
