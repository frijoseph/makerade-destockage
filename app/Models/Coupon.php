<?php

namespace App\Models;

use App\CouponType;
use App\Support\Money;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'label', 'type', 'value', 'min_subtotal', 'max_discount', 'usage_limit', 'used_count',
    'once_per_customer', 'auto_apply', 'starts_at', 'ends_at', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'once_per_customer' => 'boolean',
            'auto_apply' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Coupon>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn (Builder $query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    public static function normalize(?string $code): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $code) ?? '');
    }

    /**
     * Human readable benefit, e.g. "-10 %", "-5 $" or "Livraison offerte".
     */
    public function benefit(): string
    {
        return match ($this->type) {
            CouponType::Percent => '-'.rtrim(rtrim(number_format((float) $this->value, 2, '.', ''), '0'), '.').' %',
            CouponType::Fixed => '-'.Money::format($this->value),
            CouponType::FreeShipping => 'Livraison offerte',
        };
    }

    /**
     * Discount on the items for a given subtotal (0 for free-shipping coupons).
     */
    public function discountFor(float $subtotal): float
    {
        $discount = match ($this->type) {
            CouponType::Percent => $subtotal * (float) $this->value / 100,
            CouponType::Fixed => (float) $this->value,
            CouponType::FreeShipping => 0.0,
        };

        if ($this->max_discount !== null && (float) $this->max_discount > 0) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
