<?php

namespace App\Models;

use App\OrderChannel;
use App\OrderStatus;
use App\PaymentMethod;
use App\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number', 'customer_id', 'user_id', 'status', 'payment_status', 'payment_method', 'channel',
    'subtotal', 'shipping_fee', 'discount', 'coupon_id', 'coupon_code', 'total',
    'shipping_name', 'shipping_phone', 'shipping_city', 'shipping_address', 'notes',
    'placed_at', 'paid_at', 'delivered_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'channel' => OrderChannel::class,
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Storefront account the order belongs to (set when the buyer was logged in, or after a claim).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Orders that count toward revenue (confirmed and beyond, not cancelled).
     *
     * @param  Builder<Order>  $query
     */
    public function scopeBillable(Builder $query): void
    {
        $query->whereIn('status', OrderStatus::billable());
    }

    /**
     * Next sequential order number for the current year, e.g. MK-2026-00042 (prefix set in the settings).
     */
    public static function nextNumber(): string
    {
        $prefix = static::numberPrefix();
        $last = static::query()->where('number', 'like', $prefix.'%')->max('number');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Example number shown as a hint in the tracking form.
     */
    public static function exampleNumber(): string
    {
        return static::numberPrefix().'00001';
    }

    private static function numberPrefix(): string
    {
        return mb_strtoupper((string) Setting::get('order_prefix')).'-'.now()->format('Y').'-';
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
