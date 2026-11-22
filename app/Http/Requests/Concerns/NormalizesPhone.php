<?php

namespace App\Http\Requests\Concerns;

use App\Support\Phone;
use Illuminate\Support\Arr;

/**
 * Turns the phone typed by the customer into international format before validation,
 * using the country chosen next to the field (or the shop default).
 */
trait NormalizesPhone
{
    protected function normalizePhoneInput(string $key = 'phone', string $countryKey = 'phone_country'): void
    {
        $data = $this->all();
        $value = Arr::get($data, $key);

        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $normalized = Phone::normalize($value, (string) Arr::get($data, $countryKey));

        if ($normalized !== null) {
            Arr::set($data, $key, $normalized);
            $this->replace($data);
        }
    }
}
