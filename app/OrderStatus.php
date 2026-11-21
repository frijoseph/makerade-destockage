<?php

namespace App;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::Processing => 'En préparation',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::Processing => 'violet',
            self::Shipped => 'cyan',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }

    /**
     * Statuses whose orders count toward revenue.
     *
     * @return list<self>
     */
    public static function billable(): array
    {
        return [self::Confirmed, self::Processing, self::Shipped, self::Delivered];
    }

    /**
     * Whether stock has been taken out for an order in this status.
     */
    public function holdsStock(): bool
    {
        return $this !== self::Cancelled;
    }
}
