<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\OrderChannel;
use App\OrderStatus;
use App\PaymentMethod;
use App\PaymentStatus;
use App\StockMovementType;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Creates orders from a cart and keeps stock in sync when their status changes.
 * Prices always come from the database, never from the browser.
 */
class OrderPlacement
{
    /**
     * @param  array{name: string, phone: string, email?: string|null, city?: string|null, address?: string|null}  $customer
     * @param  array<int|string, int>  $lines  product id => quantity
     *
     * @throws DomainException when the cart is empty or a product is unavailable
     */
    public static function place(
        array $customer,
        array $lines,
        OrderChannel $channel = OrderChannel::Web,
        PaymentMethod $paymentMethod = PaymentMethod::CashOnDelivery,
        ?string $notes = null,
        ?User $placedBy = null,
        float $discount = 0,
    ): Order {
        $lines = array_filter(array_map('intval', $lines), fn (int $quantity): bool => $quantity > 0);

        if ($lines === []) {
            throw new DomainException('Le panier est vide.');
        }

        return DB::transaction(function () use ($customer, $lines, $channel, $paymentMethod, $notes, $placedBy, $discount): Order {
            $products = Product::query()->active()->whereIn('id', array_keys($lines))->get()->keyBy('id');

            if ($products->count() !== count($lines)) {
                throw new DomainException('Un des produits du panier n’est plus disponible.');
            }

            $buyer = Customer::query()->updateOrCreate(
                ['phone' => Customer::normalizePhone($customer['phone'])],
                array_filter([
                    'name' => $customer['name'],
                    'email' => $customer['email'] ?? null,
                    'city' => $customer['city'] ?? null,
                    'address' => $customer['address'] ?? null,
                ], fn ($value): bool => $value !== null && $value !== ''),
            );

            $subtotal = 0.0;
            foreach ($lines as $productId => $quantity) {
                $subtotal += (float) $products[$productId]->price * $quantity;
            }

            $shippingFee = static::shippingFeeFor($subtotal);
            $discount = min(max($discount, 0), $subtotal);

            $order = Order::query()->create([
                'number' => Order::nextNumber(),
                'customer_id' => $buyer->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => $paymentMethod,
                'channel' => $channel,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount' => $discount,
                'total' => $subtotal + $shippingFee - $discount,
                'shipping_name' => $buyer->name,
                'shipping_phone' => $buyer->phone,
                'shipping_city' => $customer['city'] ?? $buyer->city,
                'shipping_address' => $customer['address'] ?? $buyer->address,
                'notes' => $notes,
                'placed_at' => now(),
            ]);

            foreach ($lines as $productId => $quantity) {
                $product = $products[$productId];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => trim($product->name.' — '.$product->variant, ' —'),
                    'sku' => $product->sku,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => (float) $product->price * $quantity,
                ]);

                Inventory::move($product, StockMovementType::Sale, -$quantity, "Commande {$order->number}", $order, $placedBy);
            }

            return $order->load('items', 'customer');
        });
    }

    public static function shippingFeeFor(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $threshold = (float) Setting::get('free_shipping_threshold', 0);

        return $threshold > 0 && $subtotal >= $threshold ? 0.0 : (float) Setting::get('shipping_fee', 0);
    }

    /**
     * Changes the order status, putting stock back on cancellation and taking it again on reactivation.
     *
     * @throws DomainException when reactivating an order whose stock is no longer available
     */
    public static function changeStatus(Order $order, OrderStatus $status, ?User $user = null): Order
    {
        if ($order->status === $status) {
            return $order;
        }

        return DB::transaction(function () use ($order, $status, $user): Order {
            $wasHolding = $order->status->holdsStock();
            $willHold = $status->holdsStock();

            if ($wasHolding !== $willHold) {
                foreach ($order->items()->with('product')->get() as $item) {
                    if (! $item->product) {
                        continue;
                    }

                    $willHold
                        ? Inventory::move($item->product, StockMovementType::Sale, -$item->quantity, "Réactivation {$order->number}", $order, $user)
                        : Inventory::move($item->product, StockMovementType::Return, $item->quantity, "Annulation {$order->number}", $order, $user);
                }
            }

            $order->status = $status;

            if ($status === OrderStatus::Delivered && ! $order->delivered_at) {
                $order->delivered_at = now();
            }

            $order->save();

            return $order;
        });
    }

    public static function changePaymentStatus(Order $order, PaymentStatus $paymentStatus): Order
    {
        $order->payment_status = $paymentStatus;
        $order->paid_at = $paymentStatus === PaymentStatus::Paid ? ($order->paid_at ?? now()) : null;
        $order->save();

        return $order;
    }
}
