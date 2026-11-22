<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Phone numbers are stored in international format (+2250700000000). Customers may type them with or
 * without the country code: the national part is completed with the chosen (or default) country.
 */
class Phone
{
    /**
     * Countries for the selector, ISO code => name, dial code and flag.
     *
     * @return array<string, array{name: string, dial: string, flag: string}>
     */
    public static function countries(): array
    {
        return once(function (): array {
            $countries = [];

            foreach ((array) config('phone.countries') as $iso => [$name, $dial]) {
                $countries[$iso] = ['name' => $name, 'dial' => $dial, 'flag' => static::flag($iso)];
            }

            return $countries;
        });
    }

    public static function defaultIso(): string
    {
        $iso = strtoupper((string) Setting::get('default_country'));

        return isset(static::countries()[$iso]) ? $iso : 'CI';
    }

    public static function dial(?string $iso = null): string
    {
        $iso = strtoupper((string) $iso);

        return static::countries()[$iso]['dial'] ?? static::countries()[static::defaultIso()]['dial'];
    }

    /**
     * International format of what the customer typed, or null when nothing usable was given.
     */
    public static function normalize(?string $input, ?string $iso = null): ?string
    {
        $raw = trim((string) $input);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($raw, '+')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        $dial = static::dial($iso);

        if (str_starts_with($digits, $dial) && strlen($digits) >= strlen($dial) + 9) {
            return '+'.$digits;
        }

        // Côte d'Ivoire numbers keep their leading 0 (10 digits); elsewhere the trunk 0 is dropped.
        $national = $dial === '225' ? $digits : (preg_replace('/^0/', '', $digits) ?? $digits);

        return '+'.$dial.$national;
    }

    public static function isValid(?string $e164): bool
    {
        return (bool) preg_match('/^\+\d{8,15}$/', (string) $e164);
    }

    /**
     * Country and national part of a stored number, to fill the form selector again.
     *
     * @return array{0: string, 1: string}
     */
    public static function split(?string $e164): array
    {
        $digits = ltrim((string) $e164, '+');
        $best = null;

        foreach (static::countries() as $iso => $country) {
            if (str_starts_with($digits, $country['dial']) && ($best === null || strlen($country['dial']) > strlen(static::countries()[$best]['dial']))) {
                $best = $iso;
            }
        }

        if ($best === null) {
            return [static::defaultIso(), $digits];
        }

        return [$best, substr($digits, strlen(static::countries()[$best]['dial']))];
    }

    /**
     * Readable form: +225 07 00 00 00 01.
     */
    public static function format(?string $e164): string
    {
        if (! $e164 || ! str_starts_with($e164, '+')) {
            return (string) $e164;
        }

        [$iso, $national] = static::split($e164);

        return '+'.static::countries()[$iso]['dial'].' '.trim(chunk_split($national, 2, ' '));
    }

    private static function flag(string $iso): string
    {
        $flag = '';

        foreach (str_split(strtoupper($iso)) as $letter) {
            $flag .= mb_chr(0x1F1E6 + ord($letter) - ord('A'));
        }

        return $flag;
    }
}
