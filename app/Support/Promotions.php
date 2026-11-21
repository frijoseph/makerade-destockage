<?php

namespace App\Support;

use App\CouponType;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\OrderStatus;
use DomainException;

/**
 * Promo codes and automatic promotions. The same rules run for the cart preview and for the real order.
 */
class Promotions
{
    /**
     * Resolves the promotion for a cart: the typed code, or else the best automatic promotion.
     *
     * @return array{coupon: Coupon|null, discount: float, free_shipping: bool, auto: bool}
     *
     * @throws DomainException when a typed code cannot be used
     */
    public static function resolve(float $subtotal, ?string $code = null, ?string $phone = null): array
    {
        $code = Coupon::normalize($code);

        if ($code !== '') {
            $coupon = Coupon::query()->where('code', $code)->first();

            if (! $coupon) {
                throw new DomainException('Ce code promo n’existe pas.');
            }

            static::assertUsable($coupon, $subtotal, $phone);

            return static::result($coupon, $subtotal, false);
        }

        $best = null;
        foreach (Coupon::query()->usable()->where('auto_apply', true)->get() as $coupon) {
            try {
                static::assertUsable($coupon, $subtotal, $phone);
            } catch (DomainException) {
                continue;
            }

            $candidate = static::result($coupon, $subtotal, true);
            $isBetter = $best === null
                || $candidate['discount'] > $best['discount']
                || ($candidate['discount'] === $best['discount'] && $candidate['free_shipping'] && ! $best['free_shipping']);

            if ($isBetter) {
                $best = $candidate;
            }
        }

        return $best ?? ['coupon' => null, 'discount' => 0.0, 'free_shipping' => false, 'auto' => false];
    }

    /**
     * @throws DomainException with a customer-facing message
     */
    public static function assertUsable(Coupon $coupon, float $subtotal, ?string $phone = null): void
    {
        if (! $coupon->is_active || ($coupon->starts_at && $coupon->starts_at->isFuture())) {
            throw new DomainException('Ce code promo n’est pas encore actif.');
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            throw new DomainException('Ce code promo a expiré.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new DomainException('Ce code promo a atteint sa limite d’utilisation.');
        }

        if ($subtotal < (float) $coupon->min_subtotal) {
            throw new DomainException('Ce code est valable dès '.Money::format($coupon->min_subtotal).' d’achat.');
        }

        if ($coupon->once_per_customer && $phone) {
            $alreadyUsed = Order::query()
                ->where('coupon_id', $coupon->id)
                ->where('shipping_phone', Customer::normalizePhone($phone))
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->exists();

            if ($alreadyUsed) {
                throw new DomainException('Vous avez déjà utilisé ce code promo.');
            }
        }
    }

    /**
     * @return array{coupon: Coupon, discount: float, free_shipping: bool, auto: bool}
     */
    private static function result(Coupon $coupon, float $subtotal, bool $auto): array
    {
        return [
            'coupon' => $coupon,
            'discount' => $coupon->discountFor($subtotal),
            'free_shipping' => $coupon->type === CouponType::FreeShipping,
            'auto' => $auto,
        ];
    }
}
