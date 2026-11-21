<?php

namespace App;

enum CouponType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeShipping = 'free_shipping';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Pourcentage',
            self::Fixed => 'Montant fixe',
            self::FreeShipping => 'Livraison offerte',
        };
    }
}
