<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\Media\MediaLibrary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    private const PER_PAGE = 48;

    public function index(Request $request): View
    {
        $current = $this->resolveScope($request->query('folder'));
        $files = $this->scopedFiles($current, $request->query('q'))->paginate(self::PER_PAGE)->withQueryString();
        $paths = $files->getCollection()->pluck('path')->all();

        return view('admin.media.index', [
            'folders' => $this->folderList(),
            'scope' => $request->query('folder', 'all'),
            'folder' => $current['folder'],
            'files' => $files,
            'usage' => collect(Product::query()->whereIn('image', $paths)->pluck('image'))
                ->merge(ProductImage::query()->whereIn('path', $paths)->pluck('path'))
                ->merge(ProductVariant::query()->whereIn('image', $paths)->pluck('image'))
                ->countBy()->all(),
            'totals' => [
                'files' => MediaFile::query()->count(),
                'bytes' => (int) MediaFile::query()->sum('size'),
                'saved' => (int) MediaFile::query()->selectRaw('COALESCE(SUM(CASE WHEN original_size > size THEN original_size - size ELSE 0 END), 0) as saved')->value('saved'),
            ],
            'accept' => collect(MediaLibrary::allowedExtensions())->map(fn (string $extension): string => '.'.$extension)->implode(','),
            'maxMb' => min((int) Setting::get('media_max_upload_mb'), $this->serverLimitMb()),
            'serverMb' => $this->serverLimitMb(),
            'compression' => [
                'enabled' => (bool) Setting::get('media_compress_enabled'),
                'dimension' => (int) Setting::get('media_max_dimension'),
                'quality' => (int) Setting::get('media_quality'),
                'webp' => (bool) Setting::get('media_convert_webp'),
            ],
        ]);
    }

    /**
     * JSON listing used by the picker shown in the product form.
     */
    public function browse(Request $request): JsonResponse
    {
        $current = $this->resolveScope($request->query('folder'));
        $query = $this->scopedFiles($current, $request->query('q'));

        if ($request->query('type') === 'image') {
            $query->where('mime', 'like', 'image/%');
        }

        $page = $query->paginate(36);

        return response()->json([
            'folders' => $this->folderList(),
            'files' => $page->getCollection()->map(fn (MediaFile $file): array => $file->toPickerArray())->values(),
            'next' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }

    public function storeFolder(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']], ['name.required' => 'Donnez un nom au dossier.']);

        $folder = MediaFolder::query()->create(['name' => trim($data['name'])]);

        return response()->json(['message' => "Dossier « {$folder->name} » créé.", 'id' => $folder->id], 201);
    }

    public function updateFolder(Request $request, MediaFolder $folder): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']], ['name.required' => 'Donnez un nom au dossier.']);

        $folder->update(['name' => trim($data['name'])]);

        return response()->json(['message' => 'Dossier renommé.']);
    }

    /**
     * The files of a deleted folder are not lost: they go back to the root of the library.
     */
    public function destroyFolder(MediaFolder $folder): JsonResponse
    {
        $count = $folder->files()->count();
        $folder->delete();

        return response()->json(['message' => $count ? "Dossier supprimé. {$count} fichier(s) conservé(s) hors dossier." : 'Dossier supprimé.']);
    }

    /**
     * Uploads one or several files; each one is compressed and reported separately so a bad file does not block the others.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
            'files' => ['required', 'array', 'max:30'],
        ], ['files.required' => 'Choisissez au moins un fichier.', 'files.max' => '30 fichiers au maximum par envoi.']);

        $folder = $request->filled('folder_id') ? MediaFolder::query()->find($request->integer('folder_id')) : null;
        $maxKb = max(1, (int) Setting::get('media_max_upload_mb')) * 1024;
        $stored = [];
        $errors = [];

        foreach ((array) $request->file('files', []) as $upload) {
            if (! $upload instanceof UploadedFile) {
                continue;
            }

            $name = $upload->getClientOriginalName();

            if (! $upload->isValid()) {
                $errors[] = "{$name} : envoi interrompu (fichier trop lourd pour le serveur ?).";

                continue;
            }

            if ($upload->getSize() > $maxKb * 1024) {
                $errors[] = "{$name} : dépasse ".($maxKb / 1024).' Mo.';

                continue;
            }

            $extension = strtolower((string) ($upload->guessExtension() ?: $upload->getClientOriginalExtension()));

            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            if (! in_array($extension, array_map(fn (string $item): string => $item === 'jpeg' ? 'jpg' : $item, MediaLibrary::allowedExtensions()), true)) {
                $errors[] = "{$name} : type de fichier non accepté.";

                continue;
            }

            $stored[] = MediaLibrary::store($upload, $folder);
        }

        $saved = (int) collect($stored)->sum(fn (MediaFile $file): int => $file->savedBytes());
        $message = count($stored).' fichier(s) ajouté(s)'.($saved > 0 ? ' · '.self::bytes($saved).' économisés grâce à la compression' : '').'.';

        return response()->json(['message' => $message, 'errors' => $errors, 'files' => collect($stored)->map(fn (MediaFile $file): array => $file->toPickerArray())->all()], $stored === [] ? 422 : 201);
    }

    public function updateFile(Request $request, MediaFile $file): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'media_folder_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media_folders', 'id')],
        ]);

        $file->update($data);

        return response()->json(['message' => 'Fichier mis à jour.']);
    }

    public function destroyFile(MediaFile $file): JsonResponse
    {
        try {
            MediaLibrary::delete($file);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Fichier supprimé.']);
    }

    /**
     * Re-compresses the images of a folder (or of the whole library) with the current settings.
     */
    public function optimize(Request $request): JsonResponse
    {
        $request->validate(['folder' => ['nullable', 'string', 'max:20']]);
        $current = $this->resolveScope($request->input('folder'));
        $saved = 0;
        $processed = 0;

        $this->scopedFiles($current, null)->where('mime', 'like', 'image/%')->each(function (MediaFile $file) use (&$saved, &$processed): void {
            $saved += MediaLibrary::optimize($file);
            $processed++;
        });

        return response()->json(['message' => $processed.' image(s) analysée(s) : '.($saved > 0 ? self::bytes($saved).' économisés.' : 'déjà optimisées.')]);
    }

    public function zip(string $folder = 'none'): BinaryFileResponse
    {
        $model = $folder === 'none' ? null : MediaFolder::query()->findOrFail($folder);
        $path = MediaLibrary::zip($model);

        return response()->download($path, Str::slug($model?->name ?? 'mediatheque').'.zip')->deleteFileAfterSend(true);
    }

    /**
     * @return array{type: string, folder: MediaFolder|null}
     */
    private function resolveScope(mixed $scope): array
    {
        if ($scope === 'none') {
            return ['type' => 'none', 'folder' => null];
        }

        if (is_numeric($scope) && ($folder = MediaFolder::query()->find((int) $scope))) {
            return ['type' => 'folder', 'folder' => $folder];
        }

        return ['type' => 'all', 'folder' => null];
    }

    /**
     * @param  array{type: string, folder: MediaFolder|null}  $scope
     * @return Builder<MediaFile>
     */
    private function scopedFiles(array $scope, mixed $search): Builder
    {
        return MediaFile::query()
            ->when($scope['type'] === 'folder', fn ($query) => $query->where('media_folder_id', $scope['folder']->id))
            ->when($scope['type'] === 'none', fn ($query) => $query->whereNull('media_folder_id'))
            ->when(is_string($search) && trim($search) !== '', fn ($query) => $query->where('name', 'like', '%'.trim($search).'%'))
            ->latest('id');
    }

    /**
     * @return array{all: int, none: int, folders: list<array{id: int, name: string, count: int}>}
     */
    private function folderList(): array
    {
        return [
            'all' => MediaFile::query()->count(),
            'none' => MediaFile::query()->whereNull('media_folder_id')->count(),
            'folders' => MediaFolder::query()->withCount('files')->orderBy('name')->get()
                ->map(fn (MediaFolder $folder): array => ['id' => $folder->id, 'name' => $folder->name, 'count' => $folder->files_count, 'product' => $folder->product_id !== null])->all(),
        ];
    }

    /**
     * Largest file the PHP configuration lets through (upload_max_filesize and post_max_size), in whole megabytes.
     */
    private function serverLimitMb(): int
    {
        $limits = array_filter([
            (int) ini_parse_quantity((string) ini_get('upload_max_filesize')),
            (int) ini_parse_quantity((string) ini_get('post_max_size')),
        ], fn (int $bytes): bool => $bytes > 0);

        return $limits === [] ? 50 : max(1, (int) floor(min($limits) / 1048576));
    }

    public static function bytes(int $bytes): string
    {
        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1, ',', ' ').' Mo',
            $bytes >= 1024 => number_format($bytes / 1024, 0, ',', ' ').' Ko',
            default => $bytes.' o',
        };
    }
}
