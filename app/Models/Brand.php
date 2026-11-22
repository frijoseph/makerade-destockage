<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'color', 'style', 'logo', 'url', 'is_active', 'is_featured', 'position'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    /**
     * Logo styles understood by the storefront brand-logo component.
     *
     * @var list<string>
     */
    public const STYLES = ['plain', 'pill', 'script', 'swash', 'serif', 'circle'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param  Builder<Brand>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Shape expected by the storefront views.
     *
     * @return array{name: string, color: string, style: string, logo: string|null, featured: bool, url: string|null}
     */
    public function toCatalogArray(): array
    {
        return [
            'name' => $this->name,
            'featured' => $this->is_featured,
            'url' => $this->url ? Setting::link($this->url) : null,
            'color' => $this->color,
            'style' => $this->style,
            'logo' => Setting::imageUrl($this->logo),
        ];
    }
}
