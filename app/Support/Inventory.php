<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\StockMovementType;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Every stock change goes through here so the product stock and the movement log never disagree.
 */
class Inventory
{
    /**
     * Applies a signed quantity to a product's stock and records the movement.
     *
     * @throws DomainException when the stock would become negative
     */
    public static function move(
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?string $reason = null,
        ?Order $order = null,
        ?User $user = null,
        ?ProductVariant $variant = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $reason, $order, $user, $variant): StockMovement {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $lockedVariant = $variant ? ProductVariant::query()->lockForUpdate()->findOrFail($variant->id) : null;
            $current = $lockedVariant?->stock ?? $locked->stock;
            $newStock = $current + $quantity;

            if ($newStock < 0) {
                $label = $lockedVariant ? "{$locked->name} ({$lockedVariant->name})" : $locked->name;

                throw new DomainException("Stock insuffisant pour « {$label} » ({$current} disponible(s)).");
            }

            if ($lockedVariant) {
                $lockedVariant->update(['stock' => $newStock]);
                $variant->stock = $newStock;
                $locked->update(['stock' => (int) ProductVariant::query()->where('product_id', $locked->id)->sum('stock')]);
                $product->stock = $locked->stock;
            } else {
                $locked->update(['stock' => $newStock]);
                $product->stock = $newStock;
            }

            return StockMovement::query()->create([
                'product_id' => $locked->id,
                'variant_id' => $lockedVariant?->id,
                'order_id' => $order?->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_after' => $newStock,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Sets the stock to an exact counted value (inventory count).
     */
    public static function setTo(Product $product, int $countedStock, ?string $reason = null, ?User $user = null, ?ProductVariant $variant = null): ?StockMovement
    {
        $difference = $countedStock - ($variant?->stock ?? $product->stock);

        if ($difference === 0) {
            return null;
        }

        return static::move($product, StockMovementType::Adjustment, $difference, $reason ?? 'Inventaire', user: $user, variant: $variant);
    }
}
