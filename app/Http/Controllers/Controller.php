<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

abstract class Controller
{
    /**
     * Handles an optional image field: new upload replaces the old file, "remove_{field}" clears it.
     * Bundled images (img/...) are never deleted from disk.
     */
    protected function syncImage(Request $request, string $field, string $directory, ?string $current): ?string
    {
        $replace = $request->hasFile("{$field}_file");

        if (! $replace && ! $request->boolean("remove_{$field}")) {
            return $current;
        }

        if ($current && ! str_starts_with($current, 'img/')) {
            Storage::disk('public')->delete($current);
        }

        return $replace ? $request->file("{$field}_file")->store($directory, 'public') : null;
    }
}
