<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Ivorian cities used for demo customers.
     *
     * @var list<string>
     */
    public const CITIES = ['Abidjan', 'Bouaké', 'Yamoussoukro', 'San-Pédro', 'Daloa', 'Korhogo', 'Man', 'Grand-Bassam'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake('fr_FR')->name(),
            'phone' => '+22507'.fake()->unique()->numerify('########'),
            'email' => fake()->optional(0.6)->safeEmail(),
            'city' => fake()->randomElement(self::CITIES),
            'address' => fake('fr_FR')->streetAddress(),
        ];
    }
}
