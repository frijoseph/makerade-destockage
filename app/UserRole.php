<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Manager => 'Gestionnaire',
            self::Customer => 'Client',
        };
    }

    public function canAccessAdmin(): bool
    {
        return $this !== self::Customer;
    }
}
