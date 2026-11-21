<?php

namespace App;

enum OrderChannel: string
{
    case Web = 'web';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case Store = 'store';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Site web',
            self::WhatsApp => 'WhatsApp',
            self::Phone => 'Téléphone',
            self::Store => 'Magasin',
        };
    }
}
