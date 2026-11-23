<?php

namespace App\Support\Media;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * The shop's media library: folders of files on the public disk. Every image is compressed on the way in
 * (size cap, quality, WebP) according to the settings, and the photos uploaded from the product form
 * are filed automatically in a folder named after the product.
 */
class MediaLibrary
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return Setting::get('media_allow_documents') ? [...self::IMAGE_EXTENSIONS, ...self::DOCUMENT_EXTENSIONS] : self::IMAGE_EXTENSIONS;
    }

    /**
     * Stores an uploaded file (compressing it when it is an image) and registers it in the library.
     */
    public static function store(UploadedFile $upload, ?MediaFolder $folder = null): MediaFile
    {
        $original = (string) $upload->getClientOriginalName();
        $extension = strtolower((string) ($upload->guessExtension() ?: $upload->getClientOriginalExtension()));
        $mime = (string) ($upload->getMimeType() ?: 'application/octet-stream');
        $contents = (string) file_get_contents($upload->getRealPath());
        $originalSize = strlen($contents);
        $width = $height = null;

        if (str_starts_with($mime, 'image/')) {
            [$contents, $mime, $extension, $width, $height] = self::compress($contents, $mime, $extension);
        }

        $path = 'media/'.($folder?->id ?? 'root').'/'.Str::random(24).'.'.$extension;
        Storage::disk('public')->put($path, $contents);

        return MediaFile::query()->create([
            'media_folder_id' => $folder?->id,
            'name' => self::displayName($original, $extension),
            'path' => $path,
            'mime' => $mime,
            'size' => strlen($contents),
            'original_size' => $originalSize,
            'width' => $width,
            'height' => $height,
        ]);
    }

    /**
     * Photo uploaded from the product form: compressed, filed in the product's folder, path returned for the product.
     */
    public static function storeForProduct(UploadedFile $upload, Product $product): string
    {
        return self::store($upload, self::folderFor($product))->path;
    }

    /**
     * Folder named after the product (created on first use) when automatic filing is enabled.
     */
    public static function folderFor(Product $product): ?MediaFolder
    {
        if (! Setting::get('media_product_folders')) {
            return null;
        }

        $folder = MediaFolder::query()->firstOrCreate(['product_id' => $product->id], ['name' => Str::limit($product->name, 120, '')]);

        if ($folder->name !== Str::limit($product->name, 120, '')) {
            $folder->update(['name' => Str::limit($product->name, 120, '')]);
        }

        return $folder;
    }

    /**
     * Re-compresses a stored image with the current settings, keeping its format so product links stay valid.
     */
    public static function optimize(MediaFile $file): int
    {
        if (! $file->isImage() || ! Storage::disk('public')->exists($file->path)) {
            return 0;
        }

        $before = (int) Storage::disk('public')->size($file->path);
        $result = ImageOptimizer::optimize(
            (string) Storage::disk('public')->get($file->path),
            $file->mime,
            (int) Setting::get('media_max_dimension'),
            (int) Setting::get('media_quality'),
            false,
        );

        if (! $result || strlen($result['contents']) >= $before) {
            return 0;
        }

        Storage::disk('public')->put($file->path, $result['contents']);
        $file->update(['size' => strlen($result['contents']), 'width' => $result['width'], 'height' => $result['height']]);

        return $before - strlen($result['contents']);
    }

    /**
     * Registers a file that already lives on the public disk (e.g. a product photo uploaded before the library existed).
     */
    public static function register(string $path, ?MediaFolder $folder = null): ?MediaFile
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $mime = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
        $info = str_starts_with($mime, 'image/') ? @getimagesizefromstring((string) $disk->get($path)) : false;

        return MediaFile::query()->firstOrCreate(['path' => $path], [
            'media_folder_id' => $folder?->id,
            'name' => basename($path),
            'mime' => $mime,
            'size' => (int) $disk->size($path),
            'original_size' => (int) $disk->size($path),
            'width' => $info[0] ?? null,
            'height' => $info[1] ?? null,
        ]);
    }

    /**
     * How many products use this file (main photo, gallery or variant photo).
     */
    public static function usageCount(MediaFile $file): int
    {
        return Product::query()->where('image', $file->path)->count()
            + ProductImage::query()->where('path', $file->path)->count()
            + ProductVariant::query()->where('image', $file->path)->count();
    }

    public static function isInLibrary(?string $path): bool
    {
        return $path !== null && $path !== '' && MediaFile::query()->where('path', $path)->exists();
    }

    /**
     * Deletes the file and its record; refused while a product still uses it.
     *
     * @throws RuntimeException
     */
    public static function delete(MediaFile $file): void
    {
        if ($used = self::usageCount($file)) {
            throw new RuntimeException("« {$file->name} » est utilisé par {$used} produit(s) : retirez-le d’abord de ces fiches.");
        }

        Storage::disk('public')->delete($file->path);
        $file->delete();
    }

    /**
     * Zip of a folder (or of the files outside any folder when $folder is null), written to a temporary file.
     */
    public static function zip(?MediaFolder $folder): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'media');
        $zip = new ZipArchive;

        if ($zip->open($temp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de créer l’archive.');
        }

        $used = [];

        MediaFile::query()->where('media_folder_id', $folder?->id)->orderBy('id')->each(function (MediaFile $file) use ($zip, &$used): void {
            if (! Storage::disk('public')->exists($file->path)) {
                return;
            }

            $name = $file->name;
            $used[$name] = ($used[$name] ?? 0) + 1;

            if ($used[$name] > 1) {
                $name = pathinfo($name, PATHINFO_FILENAME).' ('.$used[$name].').'.pathinfo($name, PATHINFO_EXTENSION);
            }

            $zip->addFromString($name, (string) Storage::disk('public')->get($file->path));
        });

        $zip->close();

        return $temp;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: int|null, 4: int|null}
     */
    private static function compress(string $contents, string $mime, string $extension): array
    {
        $info = @getimagesizefromstring($contents);
        $width = $info[0] ?? null;
        $height = $info[1] ?? null;

        if (! Setting::get('media_compress_enabled')) {
            return [$contents, $mime, $extension, $width, $height];
        }

        $result = ImageOptimizer::optimize($contents, $mime, (int) Setting::get('media_max_dimension'), (int) Setting::get('media_quality'), (bool) Setting::get('media_convert_webp'));

        if (! $result) {
            return [$contents, $mime, $extension, $width, $height];
        }

        return [$result['contents'], $result['mime'], $result['extension'], $result['width'], $result['height']];
    }

    private static function displayName(string $original, string $extension): string
    {
        $base = Str::limit(trim(pathinfo($original, PATHINFO_FILENAME)) ?: 'fichier', 80, '');

        return $base.'.'.$extension;
    }
}
