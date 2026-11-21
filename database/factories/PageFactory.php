<?php

namespace Database\Factories;

use App\Models\Page;
use App\PageType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::ucfirst(fake('fr_FR')->words(4, true));

        return [
            'type' => PageType::Page,
            'slug' => Str::slug($title),
            'title' => $title,
            'excerpt' => fake('fr_FR')->sentence(),
            'body' => "## Introduction\n\n".fake('fr_FR')->paragraph(),
            'is_published' => true,
        ];
    }

    public function article(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PageType::Article,
            'tag' => 'Conseils',
            'icon' => 'spray',
            'cover_color' => '#e6f3e1',
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }
}
