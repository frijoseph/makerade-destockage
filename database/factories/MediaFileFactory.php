<?php

namespace Database\Factories;

use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'media_folder_id' => null,
            'name' => $name.'.webp',
            'path' => 'media/root/'.$name.'.webp',
            'mime' => 'image/webp',
            'size' => 20000,
            'original_size' => 60000,
            'width' => 800,
            'height' => 600,
        ];
    }
}
