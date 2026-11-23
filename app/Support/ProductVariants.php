<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\StockMovementType;
use App\Support\Media\MediaLibrary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Saves the variants and the photo gallery submitted from the product form.
 */
class ProductVariants
{
    /**
     * Replaces the product's variants with the submitted rows (stock changes are logged as stock movements).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function sync(Product $product, array $rows, ?User $user = null): void
    {
        DB::transaction(function () use ($product, $rows, $user): void {
            $existing = $product->variants()->get()->keyBy('id');
            $kept = [];

            foreach (array_values($rows) as $position => $row) {
                $options = collect([1, 2, 3])->map(fn (int $n): ?string => trim((string) ($row["option{$n}"] ?? '')) ?: null)->all();
                $variant = isset($row['id']) && $existing->has((int) $row['id']) ? $existing[(int) $row['id']] : null;

                $attributes = [
                    'name' => ProductVariant::labelFor($options),
                    'option1' => $options[0],
                    'option2' => $options[1],
                    'option3' => $options[2],
                    'sku' => ($row['sku'] ?? '') !== '' ? trim((string) $row['sku']) : null,
                    'price' => ($row['price'] ?? '') !== '' ? $row['price'] : null,
                    'old_price' => ($row['old_price'] ?? '') !== '' ? $row['old_price'] : null,
                    'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'position' => $position,
                ];

                $upload = $row['image'] ?? null;
                if ($upload instanceof UploadedFile) {
                    static::deleteFile($variant?->image);
                    $attributes['image'] = MediaLibrary::storeForProduct($upload, $product);
                } elseif (! empty($row['remove_image']) && $variant) {
                    static::deleteFile($variant->image);
                    $attributes['image'] = null;
                }

                $isNew = $variant === null;
                $variant = $isNew ? $product->variants()->create($attributes + ['stock' => 0]) : tap($variant)->update($attributes);
                $kept[] = $variant->id;

                $difference = (int) ($row['stock'] ?? 0) - $variant->stock;
                if ($difference !== 0) {
                    Inventory::move(
                        $product,
                        $isNew ? StockMovementType::Restock : StockMovementType::Adjustment,
                        $difference,
                        $isNew ? 'Stock initial' : 'Ajustement depuis la fiche produit',
                        user: $user,
                        variant: $variant,
                    );
                }
            }

            foreach ($existing->except($kept) as $removed) {
                if ($removed->orderItems()->exists()) {
                    $removed->update(['is_active' => false]);

                    continue;
                }

                if ($removed->stock !== 0) {
                    Inventory::move($product, StockMovementType::Adjustment, -$removed->stock, 'Variante supprimée', user: $user, variant: $removed);
                }

                static::deleteFile($removed->image);
                $removed->delete();
            }

            if ($product->variants()->exists()) {
                $product->update(['stock' => (int) $product->variants()->sum('stock')]);
            }
        });
    }

    /**
     * Adds uploaded photos, removes the ticked ones and promotes one secondary photo to main photo.
     *
     * @param  list<UploadedFile>  $uploads
     * @param  list<int|string>  $removeIds
     * @param  list<string>  $mediaPaths  files picked in the media library
     */
    public static function syncGallery(Product $product, array $uploads, array $removeIds, ?int $promoteId, array $mediaPaths = []): void
    {
        $product->images()->whereIn('id', $removeIds)->get()->each(function (ProductImage $image): void {
            static::deleteFile($image->path);
            $image->delete();
        });

        if ($promoteId && ($promoted = $product->images()->find($promoteId))) {
            $previousMain = $product->image;
            $product->update(['image' => $promoted->path]);

            if ($previousMain) {
                $promoted->update(['path' => $previousMain]);
            } else {
                $promoted->delete();
            }
        }

        $position = (int) $product->images()->max('position');
        foreach ($uploads as $upload) {
            $product->images()->create(['path' => MediaLibrary::storeForProduct($upload, $product), 'position' => ++$position]);
        }

        $already = $product->images()->pluck('path')->push($product->image)->filter()->all();
        foreach (array_diff(array_unique($mediaPaths), $already) as $path) {
            $product->images()->create(['path' => $path, 'position' => ++$position]);
        }
    }

    /**
     * Removes every stored file of a product that is being deleted.
     */
    public static function deleteMedia(Product $product): void
    {
        static::deleteFile($product->image);
        $product->images()->get()->each(fn (ProductImage $image) => static::deleteFile($image->path));
        $product->variants()->get()->each(fn (ProductVariant $variant) => static::deleteFile($variant->image));
    }

    /**
     * Files that live in the media library are kept: only the product's link to them is removed.
     */
    private static function deleteFile(?string $path): void
    {
        if ($path && ! MediaLibrary::isInLibrary($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
