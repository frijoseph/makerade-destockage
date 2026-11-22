<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Decides when a customer record (identified by a phone number that nobody has verified) may be attached
 * to a storefront account. Attaching it exposes the whole order history, so it is only done when
 * there is nothing to expose, or after the person proved they own an order.
 */
class AccountLinker
{
    /**
     * Links the customer to the account when the customer is new (no order yet) or already the account's.
     * A customer with past orders stays untouched: the account must claim them with {@see self::claim()}.
     */
    public static function linkIfSafe(User $user, Customer $customer): bool
    {
        if ($customer->user_id === $user->id) {
            return true;
        }

        if ($customer->user_id !== null || $customer->orders()->exists()) {
            return false;
        }

        $customer->update(['user_id' => $user->id]);

        return true;
    }

    /**
     * Brings past orders into the account after a proof of possession: the order number AND the amount paid.
     * The order must have been placed with the account's own phone number.
     *
     * @return int number of orders now in the account
     */
    public static function claim(User $user, string $number, float|int|string $total): int
    {
        $order = Order::query()
            ->where('number', mb_strtoupper(trim($number)))
            ->where('shipping_phone', (string) $user->phone)
            ->with('customer')
            ->first();

        if (! $order || abs((float) $order->total - self::amount($total)) > 0.009 || ($order->user_id && $order->user_id !== $user->id)) {
            return 0;
        }

        return DB::transaction(function () use ($user, $order): int {
            $customer = $order->customer;

            if ($customer->user_id === null) {
                $customer->update(['user_id' => $user->id]);
            }

            if ($customer->user_id !== $user->id) {
                return 0;
            }

            Order::query()->where('customer_id', $customer->id)->whereNull('user_id')->update(['user_id' => $user->id]);

            return Order::query()->where('customer_id', $customer->id)->where('user_id', $user->id)->count();
        });
    }

    private static function amount(float|int|string $value): float
    {
        $clean = str_replace([' ', "\u{00A0}", "\u{202F}"], '', (string) $value);

        return (float) str_replace(',', '.', $clean);
    }
}
