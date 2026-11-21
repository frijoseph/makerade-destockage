<?php

namespace Database\Seeders;

use App\CouponType;
use App\Models\Coupon;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Example promo codes used by the demo pop-ups and the spin-to-win wheel. Safe to run several times.
 */
class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            ['BIENVENUE10', 'Offre de bienvenue', CouponType::Percent, 10, 0, null, null, true, false, null],
            ['LIVRAISON', 'Livraison offerte dès 25', CouponType::FreeShipping, 0, 25, null, null, false, false, null],
            ['ROUE15', 'Gain de la roue : -15 %', CouponType::Percent, 15, 0, 20, null, true, false, null],
            ['FLASH20', 'Vente flash du week-end', CouponType::Percent, 20, 50, 15, 200, false, false, now()->addDays(7)],
            ['REMISE5', 'Remise automatique dès 80', CouponType::Fixed, 5, 80, null, null, false, true, null],
        ];

        foreach ($coupons as [$code, $label, $type, $value, $minimum, $maxDiscount, $limit, $oncePerCustomer, $auto, $endsAt]) {
            Coupon::query()->firstOrCreate(['code' => $code], [
                'label' => $label,
                'type' => $type,
                'value' => $value,
                'min_subtotal' => $minimum,
                'max_discount' => $maxDiscount,
                'usage_limit' => $limit,
                'once_per_customer' => $oncePerCustomer,
                'auto_apply' => $auto,
                'ends_at' => $endsAt,
                'is_active' => true,
            ]);
        }

        if (! Setting::get('welcome_coupon_code')) {
            Setting::putMany(['welcome_coupon_code' => 'BIENVENUE10']);
        }
    }
}
