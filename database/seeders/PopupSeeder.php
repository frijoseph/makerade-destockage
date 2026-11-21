<?php

namespace Database\Seeders;

use App\Models\Popup;
use Illuminate\Database\Seeder;

/**
 * Example campaigns: a spin-to-win wheel, an exit-intent newsletter offer and a cart reminder.
 * Only created when no pop-up exists yet; everything is editable in Popups & roue.
 */
class PopupSeeder extends Seeder
{
    public function run(): void
    {
        if (Popup::query()->exists()) {
            return;
        }

        Popup::query()->create([
            'name' => 'Roue de bienvenue',
            'kind' => 'wheel',
            'title' => 'Tentez votre chance ! 🎡',
            'body' => 'Tournez la roue et gagnez une réduction ou la livraison offerte.',
            'trigger' => 'delay',
            'trigger_value' => 12,
            'frequency' => 'once',
            'pages' => ['home', 'shop'],
            'priority' => 10,
            'accent' => '#237a32',
            'segments' => [
                ['label' => '-10 %', 'coupon_code' => 'BIENVENUE10', 'weight' => 25, 'color' => '#237a32'],
                ['label' => 'Perdu', 'coupon_code' => '', 'weight' => 20, 'color' => '#9aa3a0'],
                ['label' => 'Livraison offerte', 'coupon_code' => 'LIVRAISON', 'weight' => 20, 'color' => '#f2a516'],
                ['label' => 'Perdu', 'coupon_code' => '', 'weight' => 20, 'color' => '#6fae77'],
                ['label' => '-15 %', 'coupon_code' => 'ROUE15', 'weight' => 10, 'color' => '#e0364b'],
                ['label' => '-20 %', 'coupon_code' => 'FLASH20', 'weight' => 5, 'color' => '#2e8bd6'],
            ],
            'is_active' => true,
        ]);

        Popup::query()->create([
            'name' => 'Newsletter à la sortie',
            'kind' => 'newsletter',
            'title' => 'Avant de partir… 🎁',
            'body' => 'Inscrivez-vous à la newsletter et recevez un code de -10 % sur votre première commande.',
            'coupon_code' => 'BIENVENUE10',
            'button_label' => 'Je veux mon code',
            'trigger' => 'exit',
            'trigger_value' => 0,
            'frequency' => 'session',
            'pages' => ['home', 'shop', 'product'],
            'priority' => 5,
            'accent' => '#237a32',
            'is_active' => true,
        ]);

        Popup::query()->create([
            'name' => 'Rappel panier : livraison offerte',
            'kind' => 'promo',
            'title' => 'Votre panier vous attend',
            'body' => 'Finalisez votre commande maintenant : la livraison est offerte avec ce code.',
            'coupon_code' => 'LIVRAISON',
            'button_label' => 'Utiliser ce code',
            'trigger' => 'delay',
            'trigger_value' => 25,
            'frequency' => 'session',
            'pages' => ['cart'],
            'priority' => 1,
            'accent' => '#f2a516',
            'is_active' => true,
        ]);
    }
}
