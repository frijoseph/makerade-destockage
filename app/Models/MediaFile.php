<?php

namespace App\Models;

use Database\Factories\MediaFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['media_folder_id', 'name', 'path', 'mime', 'size', 'original_size', 'width', 'height'])]
class MediaFile extends Model
{
    /** @use HasFactory<MediaFileFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    public function isImage(): bool
    {
        return Str::startsWith($this->mime, 'image/');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->path));
    }

    /**
     * Bytes saved by the compression (0 when the file was not compressed or got bigger).
     */
    public function savedBytes(): int
    {
        return max(0, (int) $this->original_size - (int) $this->size);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'url' => $this->url,
            'mime' => $this->mime,
            'image' => $this->isImage(),
            'size' => $this->size,
            'folder' => $this->media_folder_id,
        ];
    }
}
