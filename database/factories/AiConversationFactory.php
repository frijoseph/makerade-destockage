<?php

namespace Database\Factories;

use App\Models\AiConversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiConversation>
 */
class AiConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(40),
            'summary' => fake()->sentence(6),
            'messages_count' => 0,
            'last_message_at' => now(),
        ];
    }
}
