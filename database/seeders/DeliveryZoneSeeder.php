<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

/**
 * Starting delivery zones. Everything is editable afterwards in Paramètres › Livraison.
 */
class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        if (DeliveryZone::query()->exists()) {
            return;
        }

        $zones = [
            ['Abidjan', 3.00, 40, '24 à 48 h'],
            ['Grand-Bassam', 4.00, null, '2 jours'],
            ['Bouaké', 6.50, 60, '2 à 3 jours'],
            ['Yamoussoukro', 6.50, 60, '2 à 3 jours'],
            ['San-Pédro', 8.00, null, '3 à 4 jours'],
            ['Daloa', 8.00, null, '3 à 4 jours'],
            ['Korhogo', 9.00, null, '3 à 5 jours'],
            ['Man', 9.00, null, '3 à 5 jours'],
        ];

        foreach ($zones as $position => [$name, $fee, $freeThreshold, $delay]) {
            DeliveryZone::query()->create([
                'name' => $name,
                'fee' => $fee,
                'free_threshold' => $freeThreshold,
                'delay' => $delay,
                'position' => $position,
                'is_active' => true,
            ]);
        }
    }
}
