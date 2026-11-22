<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'phone'])]
class NewsletterSubscriber extends Model
{
    /**
     * Registers a contact once: by phone first, then by e-mail, enriching an existing row with the missing channel.
     */
    public static function subscribe(?string $phone, ?string $email): self
    {
        $email = $email ? mb_strtolower($email) : null;

        $existing = static::query()
            ->when($phone, fn ($query) => $query->where('phone', $phone))
            ->when(! $phone && $email, fn ($query) => $query->where('email', $email))
            ->first()
            ?? ($email ? static::query()->where('email', $email)->first() : null);

        if ($existing) {
            $existing->fill(array_filter(['phone' => $existing->phone ?: $phone, 'email' => $existing->email ?: $email]))->save();

            return $existing;
        }

        return static::query()->create(['phone' => $phone, 'email' => $email]);
    }
}
