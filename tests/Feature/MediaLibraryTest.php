<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Media\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.catalog_from_db' => true]);
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    private function upload(string $name = 'photo.jpg', int $width = 3000, int $height = 2000, ?int $folder = null): TestResponse
    {
        return $this->postJson(route('admin.media.upload'), ['files' => [UploadedFile::fake()->image($name, $width, $height)], 'folder_id' => $folder]);
    }

    public function test_pages_render_and_the_library_is_in_the_menu(): void
    {
        $this->get(route('admin.media.index'))->assertOk()->assertSee('Médiathèque')->assertSee('Glissez vos fichiers');
        $this->get(route('admin.dashboard'))->assertSee(route('admin.media.index'), false);
        $this->get(route('admin.settings.edit', 'media'))->assertOk()->assertSee('Compresser automatiquement');
    }

    public function test_folders_can_be_created_renamed_and_deleted_without_losing_files(): void
    {
        $id = $this->postJson(route('admin.media.folders.store'), ['name' => 'Lessives'])->assertCreated()->json('id');
        $this->postJson(route('admin.media.folders.store'), ['name' => ''])->assertUnprocessable();

        $this->putJson(route('admin.media.folders.update', $id), ['name' => 'Lessives été'])->assertOk();
        $this->assertSame('Lessives été', MediaFolder::query()->findOrFail($id)->name);

        $this->upload('a.jpg', 400, 300, $id)->assertCreated();
        $this->get(route('admin.media.index', ['folder' => $id]))->assertOk()->assertSee('Lessives été');

        $this->deleteJson(route('admin.media.folders.destroy', $id))->assertOk();
        $this->assertSame(0, MediaFolder::query()->count());
        $this->assertSame(1, MediaFile::query()->whereNull('media_folder_id')->count(), 'files survive their folder');
    }

    public function test_images_are_compressed_resized_and_converted_to_webp(): void
    {
        $response = $this->upload('gros.jpg', 3200, 2400)->assertCreated();

        $file = MediaFile::query()->firstOrFail();
        Storage::disk('public')->assertExists($file->path);

        $this->assertSame('image/webp', $file->mime);
        $this->assertStringEndsWith('.webp', $file->path);
        $this->assertLessThanOrEqual(1600, max($file->width, $file->height));
        $this->assertSame(1600, $file->width);
        $this->assertSame(1200, $file->height);
        $this->assertSame('gros.webp', $file->name);
        $this->assertGreaterThan(0, $file->original_size);
        $this->assertSame($file->size, Storage::disk('public')->size($file->path));
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_compression_follows_the_settings(): void
    {
        Setting::putMany(['media_max_dimension' => 800, 'media_convert_webp' => false, 'media_quality' => 70]);

        $this->upload('photo.jpg', 2000, 1000)->assertCreated();
        $file = MediaFile::query()->firstOrFail();

        $this->assertSame('image/jpeg', $file->mime);
        $this->assertSame(800, $file->width);
        $this->assertSame(400, $file->height);

        Setting::putMany(['media_compress_enabled' => false]);
        $this->upload('brut.jpg', 2000, 1000)->assertCreated();
        $raw = MediaFile::query()->where('name', 'brut.jpg')->firstOrFail();
        $this->assertSame(2000, $raw->width, 'compression can be switched off');
    }

    public function test_only_accepted_file_types_are_stored(): void
    {
        $response = $this->postJson(route('admin.media.upload'), ['files' => [
            UploadedFile::fake()->create('virus.php', 5, 'application/x-php'),
            UploadedFile::fake()->create('image.svg', 5, 'image/svg+xml'),
        ]])->assertUnprocessable();

        $this->assertCount(2, $response->json('errors'));
        $this->assertSame(0, MediaFile::query()->count());

        $this->postJson(route('admin.media.upload'), ['files' => [UploadedFile::fake()->create('catalogue.pdf', 40, 'application/pdf')]])->assertCreated();
        $this->assertSame('application/pdf', MediaFile::query()->firstOrFail()->mime);

        Setting::putMany(['media_allow_documents' => false]);
        $this->postJson(route('admin.media.upload'), ['files' => [UploadedFile::fake()->create('autre.pdf', 40, 'application/pdf')]])->assertUnprocessable();
    }

    public function test_oversized_files_are_refused(): void
    {
        Setting::putMany(['media_max_upload_mb' => 1]);

        $this->postJson(route('admin.media.upload'), ['files' => [UploadedFile::fake()->create('lourd.pdf', 2048, 'application/pdf')]])
            ->assertUnprocessable()->assertJsonPath('errors.0', 'lourd.pdf : dépasse 1 Mo.');
    }

    public function test_files_can_be_renamed_moved_and_deleted(): void
    {
        $folder = MediaFolder::factory()->create(['name' => 'Promos']);
        $this->upload('a.jpg', 300, 300)->assertCreated();
        $file = MediaFile::query()->firstOrFail();

        $this->putJson(route('admin.media.files.update', $file), ['name' => 'affiche.webp', 'media_folder_id' => $folder->id])->assertOk();
        $this->assertSame($folder->id, $file->fresh()->media_folder_id);

        $this->putJson(route('admin.media.files.update', $file), ['media_folder_id' => null])->assertOk();
        $this->assertNull($file->fresh()->media_folder_id);

        $this->deleteJson(route('admin.media.files.destroy', $file))->assertOk();
        Storage::disk('public')->assertMissing($file->path);
        $this->assertSame(0, MediaFile::query()->count());
    }

    public function test_the_picker_lists_folders_and_filters_images(): void
    {
        $folder = MediaFolder::factory()->create(['name' => 'Lessives']);
        MediaFile::factory()->create(['name' => 'omo.webp', 'media_folder_id' => $folder->id]);
        MediaFile::factory()->create(['name' => 'guide.pdf', 'mime' => 'application/pdf', 'path' => 'media/root/guide.pdf']);

        $all = $this->getJson(route('admin.media.browse', ['type' => 'image']))->assertOk();
        $this->assertSame(['omo.webp'], array_column($all->json('files'), 'name'));
        $this->assertSame('Lessives', $all->json('folders.folders.0.name'));

        $inFolder = $this->getJson(route('admin.media.browse', ['folder' => $folder->id]))->assertOk();
        $this->assertCount(1, $inFolder->json('files'));

        $this->getJson(route('admin.media.browse', ['q' => 'guide']))->assertJsonCount(1, 'files');
    }

    public function test_images_uploaded_from_the_product_form_are_filed_in_a_folder_named_after_the_product(): void
    {
        $category = Category::factory()->create();
        $payload = Product::factory()->make(['category_id' => $category->id, 'name' => 'Lessive Aloe', 'slug' => 'lessive-aloe'])->toArray();
        $payload['variants'] = [];

        $this->post(route('admin.products.store'), $payload + [
            'image' => UploadedFile::fake()->image('principale.jpg', 2400, 1800),
            'gallery' => [UploadedFile::fake()->image('cote.png', 900, 900), UploadedFile::fake()->image('dos.jpg', 900, 900)],
            'stock' => 5,
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('slug', 'lessive-aloe')->firstOrFail();
        $folder = MediaFolder::query()->where('product_id', $product->id)->firstOrFail();

        $this->assertSame('Lessive Aloe', $folder->name);
        $this->assertSame(3, $folder->files()->count());
        $this->assertTrue(MediaLibrary::isInLibrary($product->image));
        $this->assertSame(2, $product->images()->count());
        $this->assertTrue($product->images->every(fn ($image): bool => MediaLibrary::isInLibrary($image->path)));
    }

    public function test_automatic_product_folders_can_be_disabled(): void
    {
        Setting::putMany(['media_product_folders' => false]);
        $product = Product::factory()->create();

        $path = MediaLibrary::storeForProduct(UploadedFile::fake()->image('a.jpg', 500, 500), $product);

        $this->assertSame(0, MediaFolder::query()->count());
        $this->assertNull(MediaFile::query()->where('path', $path)->firstOrFail()->media_folder_id);
    }

    public function test_library_files_can_be_picked_as_main_and_gallery_photos_and_survive_the_product(): void
    {
        $main = MediaFile::factory()->create(['path' => 'media/root/main.webp', 'name' => 'main.webp']);
        $extra = MediaFile::factory()->create(['path' => 'media/root/extra.webp', 'name' => 'extra.webp']);
        Storage::disk('public')->put($main->path, 'x');
        Storage::disk('public')->put($extra->path, 'y');
        $product = Product::factory()->create();

        $payload = Product::factory()->make(['category_id' => $product->category_id, 'name' => $product->name, 'slug' => $product->slug, 'sku' => $product->sku])->toArray();
        unset($payload['stock']);
        $this->put(route('admin.products.update', $product), $payload + ['image_media_id' => $main->id, 'gallery_media_ids' => [$extra->id, $main->id]])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('media/root/main.webp', $product->image);
        $this->assertSame(['media/root/extra.webp'], $product->images()->pluck('path')->all(), 'the main photo is not duplicated in the gallery');

        $this->deleteJson(route('admin.media.files.destroy', $main))->assertUnprocessable();
        Storage::disk('public')->assertExists($main->path);

        $this->delete(route('admin.products.destroy', $product));
        Storage::disk('public')->assertExists($main->path);
        Storage::disk('public')->assertExists($extra->path);
        $this->assertSame(0, MediaLibrary::usageCount($main->fresh()));
    }

    public function test_unknown_library_ids_are_rejected(): void
    {
        $product = Product::factory()->create();
        $payload = Product::factory()->make(['category_id' => $product->category_id, 'name' => $product->name, 'slug' => $product->slug, 'sku' => $product->sku])->toArray();
        unset($payload['stock']);

        $this->put(route('admin.products.update', $product), $payload + ['image_media_id' => 9999])->assertSessionHasErrors('image_media_id');
    }

    public function test_existing_images_can_be_recompressed_in_bulk(): void
    {
        Setting::putMany(['media_compress_enabled' => false]);
        $this->upload('gros.jpg', 2400, 1600)->assertCreated();
        $file = MediaFile::query()->firstOrFail();
        $before = $file->size;

        Setting::putMany(['media_compress_enabled' => true, 'media_max_dimension' => 800, 'media_quality' => 60]);
        $this->postJson(route('admin.media.optimize'), ['folder' => 'all'])->assertOk();

        $file->refresh();
        $this->assertSame('image/jpeg', $file->mime, 'the format is kept so product links stay valid');
        $this->assertSame(800, $file->width);
        $this->assertLessThan($before, $file->size);
        $this->assertGreaterThan(0, $file->savedBytes());
    }

    public function test_a_folder_can_be_downloaded_as_a_zip(): void
    {
        $folder = MediaFolder::factory()->create(['name' => 'Pack photos']);
        $this->upload('un.jpg', 300, 300, $folder->id);
        $this->upload('un.jpg', 300, 300, $folder->id);

        $response = $this->get(route('admin.media.zip', $folder))->assertOk();
        $response->assertDownload('pack-photos.zip');

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $this->assertSame(2, $zip->numFiles);
        $this->assertContains('un.webp', [$zip->getNameIndex(0), $zip->getNameIndex(1)]);
        $this->assertContains('un (2).webp', [$zip->getNameIndex(0), $zip->getNameIndex(1)]);
        $zip->close();
    }

    public function test_customers_cannot_use_the_library(): void
    {
        auth()->logout();
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.media.index'))->assertRedirect(route('admin.login'));
        $this->postJson(route('admin.media.upload'), ['files' => [UploadedFile::fake()->image('a.jpg')]])->assertUnauthorized();
    }
}
