<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Formats amounts with the currency configured in the shop settings (admin and storefront alike).
 */
class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $config = static::config();
        $number = number_format((float) $amount, $config['decimals'], $config['decimal'], $config['thousands']);

        return $config['position'] === 'after'
            ? $number."\u{00A0}".$config['symbol']
            : $config['symbol'].$number;
    }

    /**
     * Plain number with the shop's separators (review counts, quantities…).
     */
    public static function number(float|int $value, int $decimals = 0): string
    {
        $config = static::config();

        return number_format($value, $decimals, $config['decimal'], $config['thousands']);
    }

    /**
     * Short form for chart axes and KPI cards, e.g. $1.2k.
     */
    public static function compact(float|int|string|null $amount): string
    {
        $value = (float) $amount;

        if (abs($value) < 1000) {
            return static::format(round($value));
        }

        $config = static::config();
        $short = abs($value) >= 1_000_000
            ? number_format($value / 1_000_000, 1, $config['decimal'], '').'M'
            : number_format($value / 1000, 1, $config['decimal'], '').'k';

        return $config['position'] === 'after' ? $short."\u{00A0}".$config['symbol'] : $config['symbol'].$short;
    }

    /**
     * Currency settings, also handed to the storefront scripts so they format prices the same way.
     *
     * @return array{symbol: string, position: string, decimals: int, decimal: string, thousands: string}
     */
    public static function config(): array
    {
        return [
            'symbol' => (string) Setting::get('currency_symbol'),
            'position' => (string) Setting::get('currency_position'),
            'decimals' => (int) Setting::get('currency_decimals'),
            'decimal' => self::SEPARATORS[Setting::get('decimal_separator')] ?? '.',
            'thousands' => self::SEPARATORS[Setting::get('thousands_separator')] ?? ',',
        ];
    }

    /**
     * Separator names stored in the settings (whitespace would be trimmed away by the request).
     *
     * @var array<string, string>
     */
    private const SEPARATORS = ['dot' => '.', 'comma' => ',', 'space' => "\u{202F}", 'none' => ''];
}
