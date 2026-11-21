<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Formats amounts with the currency configured in the shop settings.
 */
class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $decimals = (int) Setting::get('currency_decimals', 2);
        $symbol = (string) Setting::get('currency_symbol', '$');
        $number = number_format((float) $amount, $decimals, ',', "\u{202F}");

        return Setting::get('currency_position', 'before') === 'after'
            ? $number."\u{00A0}".$symbol
            : $symbol.$number;
    }

    /**
     * Short form for chart axes and KPI cards, e.g. $1,2k.
     */
    public static function compact(float|int|string|null $amount): string
    {
        $value = (float) $amount;

        if (abs($value) < 1000) {
            return static::format(round($value));
        }

        $symbol = (string) Setting::get('currency_symbol', '$');
        $short = abs($value) >= 1_000_000
            ? number_format($value / 1_000_000, 1, ',', '').'M'
            : number_format($value / 1000, 1, ',', '').'k';

        return Setting::get('currency_position', 'before') === 'after'
            ? $short."\u{00A0}".$symbol
            : $symbol.$short;
    }
}
