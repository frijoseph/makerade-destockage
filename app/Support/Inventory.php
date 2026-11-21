<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
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
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $reason, $order, $user): StockMovement {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $newStock = $locked->stock + $quantity;

            if ($newStock < 0) {
                throw new DomainException("Stock insuffisant pour « {$locked->name} » ({$locked->stock} disponible(s)).");
            }

            $locked->update(['stock' => $newStock]);
            $product->stock = $newStock;

            return StockMovement::query()->create([
                'product_id' => $locked->id,
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
    public static function setTo(Product $product, int $countedStock, ?string $reason = null, ?User $user = null): ?StockMovement
    {
        $difference = $countedStock - $product->stock;

        if ($difference === 0) {
            return null;
        }

        return static::move($product, StockMovementType::Adjustment, $difference, $reason ?? 'Inventaire', user: $user);
    }
}
