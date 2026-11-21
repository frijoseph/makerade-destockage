<?php

namespace App;

enum StockMovementType: string
{
    case Restock = 'restock';
    case Sale = 'sale';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Loss = 'loss';

    public function label(): string
    {
        return match ($this) {
            self::Restock => 'Réapprovisionnement',
            self::Sale => 'Vente',
            self::Return => 'Retour / annulation',
            self::Adjustment => 'Ajustement inventaire',
            self::Loss => 'Perte / casse',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Restock, self::Return => 'green',
            self::Sale => 'blue',
            self::Adjustment => 'violet',
            self::Loss => 'red',
        };
    }
}
