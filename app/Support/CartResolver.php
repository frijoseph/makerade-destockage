<?php

namespace App\Support;

use App\Models\Product;
use DomainException;

/**
 * Translates the browser cart (« slug » or « slug~variantId » => quantity) into order lines.
 */
class CartResolver
{
    /**
     * @param  array<string, int|string>  $items
     * @return array<string, int>|null « productId » or « productId:variantId » => quantity, null when a product is unknown or inactive
     */
    public static function lines(array $items): ?array
    {
        $slugs = collect(array_keys($items))->map(fn (string $key): string => explode('~', $key, 2)[0])->unique();
        $ids = Product::query()->active()->whereIn('slug', $slugs)->pluck('id', 'slug');

        if ($ids->count() !== $slugs->count()) {
            return null;
        }

        $lines = [];
        foreach ($items as $key => $quantity) {
            [$slug, $variantId] = array_pad(explode('~', (string) $key, 2), 2, null);
            $lineKey = $ids[$slug].($variantId !== null && ctype_digit($variantId) ? ':'.$variantId : '');
            $lines[$lineKey] = ($lines[$lineKey] ?? 0) + (int) $quantity;
        }

        return $lines;
    }

    /**
     * Server-side subtotal of a cart (used by the promotion preview).
     *
     * @param  array<string, int|string>  $items
     *
     * @throws DomainException when a line cannot be resolved
     */
    public static function subtotal(array $items): float
    {
        $lines = static::lines($items) ?? throw new DomainException('Un des produits du panier n’est plus disponible.');

        return array_sum(array_map(
            fn (array $line): float => $line['unit_price'] * $line['quantity'],
            OrderPlacement::resolveLines($lines),
        ));
    }
}
