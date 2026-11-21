<?php

namespace App;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Non payée',
            self::Paid => 'Payée',
            self::Refunded => 'Remboursée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Unpaid => 'amber',
            self::Paid => 'green',
            self::Refunded => 'red',
        };
    }
}
