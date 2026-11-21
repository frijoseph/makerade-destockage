<?php

namespace App;

enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';
    case OrangeMoney = 'orange_money';
    case MtnMomo = 'mtn_momo';
    case Wave = 'wave';
    case MoovMoney = 'moov_money';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Paiement à la livraison',
            self::OrangeMoney => 'Orange Money',
            self::MtnMomo => 'MTN MoMo',
            self::Wave => 'Wave',
            self::MoovMoney => 'Moov Money',
            self::Card => 'Carte bancaire',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'slate',
            self::OrangeMoney => 'orange',
            self::MtnMomo => 'amber',
            self::Wave => 'cyan',
            self::MoovMoney => 'blue',
            self::Card => 'violet',
        };
    }
}
