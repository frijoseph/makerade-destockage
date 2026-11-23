<?php

namespace App\Support\Media;

use GdImage;

/**
 * Shrinks images with GD: caps the longest side, re-encodes with the configured quality and,
 * optionally, converts JPEG/PNG to WebP. The original bytes are kept when the result is not smaller.
 */
class ImageOptimizer
{
    private const OPTIMIZABLE = ['image/jpeg', 'image/png', 'image/webp'];

    /** Above this many pixels the image is stored untouched (decoding would exhaust the memory). */
    private const MAX_PIXELS = 36_000_000;

    /** The memory limit is never raised above this many bytes. */
    private const MEMORY_CEILING = 640_000_000;

    public static function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromstring');
    }

    /**
     * @return array{contents: string, mime: string, extension: string, width: int, height: int}|null null = keep the file as it is
     */
    public static function optimize(string $binary, string $mime, int $maxDimension, int $quality, bool $toWebp): ?array
    {
        if (! self::available() || ! in_array($mime, self::OPTIMIZABLE, true)) {
            return null;
        }

        $info = @getimagesizefromstring($binary);

        if (! $info || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        if (! self::ensureMemory((int) ($info[0] * $info[1] * 5.5) + strlen($binary) * 3 + 16_000_000)) {
            return null;
        }

        $source = @imagecreatefromstring($binary);

        if (! $source instanceof GdImage) {
            return null;
        }

        $source = self::orient($source, $binary, $mime);
        [$width, $height] = [imagesx($source), imagesy($source)];
        $resized = max($width, $height) > $maxDimension;

        if ($resized) {
            $ratio = $maxDimension / max($width, $height);
            $source = self::resize($source, max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));
            [$width, $height] = [imagesx($source), imagesy($source)];
        }

        $target = $toWebp && function_exists('imagewebp') ? 'image/webp' : $mime;
        $converted = $target !== $mime;

        ob_start();
        match ($target) {
            'image/webp' => imagewebp($source, null, $quality),
            'image/png' => imagepng($source, null, 9),
            default => imagejpeg($source, null, $quality),
        };
        $contents = (string) ob_get_clean();
        unset($source);

        if ($contents === '' || (! $resized && ! $converted && strlen($contents) >= strlen($binary))) {
            return null;
        }

        return ['contents' => $contents, 'mime' => $target, 'extension' => self::extensionFor($target), 'width' => $width, 'height' => $height];
    }

    /**
     * Decoding a 12-megapixel photo needs around 70 MB: raises the PHP memory limit for this request when the host allows it,
     * and tells the caller to keep the file untouched when it does not (better an uncompressed image than a crash).
     */
    private static function ensureMemory(int $needed): bool
    {
        $limit = (int) ini_parse_quantity((string) ini_get('memory_limit'));

        if ($limit <= 0) {
            return true;
        }

        $free = $limit - memory_get_usage(true);

        if ($needed <= $free) {
            return true;
        }

        $target = $limit + ($needed - $free) + 32_000_000;

        return $target <= self::MEMORY_CEILING && @ini_set('memory_limit', (string) $target) !== false;
    }

    public static function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/webp' => 'webp',
            'image/png' => 'png',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }

    private static function resize(GdImage $source, int $width, int $height): GdImage
    {
        $copy = imagecreatetruecolor($width, $height);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagefill($copy, 0, 0, (int) imagecolorallocatealpha($copy, 255, 255, 255, 127));
        imagecopyresampled($copy, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

        return $copy;
    }

    /**
     * Applies the EXIF orientation of phone photos so they are not stored sideways once the metadata is dropped.
     */
    private static function orient(GdImage $image, string $binary, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($binary));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
