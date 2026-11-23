<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MediaFile;
use App\Models\Product;
use App\Models\User;
use App\Support\Media\MediaLibrary;
use App\Support\OrderPlacement;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_deactivated_customer_is_signed_out_on_the_next_request(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer, 'is_active' => true]);

        $this->actingAs($user)->get(route('account'))->assertOk();

        $user->update(['is_active' => false]);

        $this->get(route('account'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_a_deactivated_admin_loses_the_back_office_immediately(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $admin->update(['is_active' => false]);

        $this->get(route('admin.dashboard'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_an_order_cannot_rewrite_the_profile_of_a_customer_who_belongs_to_an_account(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+2250700000001']);
        Customer::query()->create(['user_id' => $owner->id, 'name' => 'Awa Koné', 'phone' => '+2250700000001', 'city' => 'Abidjan', 'address' => 'Cocody']);
        $product = Product::factory()->create(['stock' => 5]);

        $order = OrderPlacement::place(['name' => 'Intrus', 'phone' => '+2250700000001', 'city' => 'Korhogo', 'address' => 'Ailleurs', 'email' => 'intrus@example.test'], [$product->id => 1]);

        $profile = Customer::query()->where('phone', '+2250700000001')->firstOrFail();
        $this->assertSame('Awa Koné', $profile->name);
        $this->assertSame('Abidjan', $profile->city);
        $this->assertSame('Cocody', $profile->address);
        $this->assertNull($profile->email);
        $this->assertSame($owner->id, $profile->user_id);

        $this->assertSame('Intrus', $order->shipping_name);
        $this->assertSame('Ailleurs', $order->shipping_address);
    }

    public function test_a_guest_customer_is_still_updated_by_a_new_order(): void
    {
        Customer::query()->create(['name' => 'Ancien', 'phone' => '+2250700000002', 'city' => 'Abidjan']);
        $product = Product::factory()->create(['stock' => 5]);

        OrderPlacement::place(['name' => 'Nouveau', 'phone' => '+2250700000002', 'city' => 'Bouaké'], [$product->id => 1]);

        $profile = Customer::query()->where('phone', '+2250700000002')->firstOrFail();
        $this->assertSame('Nouveau', $profile->name);
        $this->assertSame('Bouaké', $profile->city);
    }

    public function test_media_file_names_cannot_escape_the_zip_folder(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $id = $this->postJson(route('admin.media.upload'), ['files' => [UploadedFile::fake()->image('photo.jpg', 200, 200)]])->assertCreated()->json('files.0.id');

        $this->putJson(route('admin.media.files.update', $id), ['name' => '../../etc/passwd'])->assertOk();
        $this->assertSame('passwd', MediaFile::query()->findOrFail($id)->name);

        MediaFile::query()->whereKey($id)->update(['name' => '../../evil.jpg']);
        $path = MediaLibrary::zip(null);

        $zip = new ZipArchive;
        $zip->open($path);
        $names = array_map(fn (int $index): string => (string) $zip->getNameIndex($index), range(0, $zip->numFiles - 1));
        $zip->close();
        @unlink($path);

        $this->assertSame(['evil.jpg'], $names);
    }

    public function test_safe_name_strips_folders_and_control_characters(): void
    {
        $this->assertSame('a.jpg', MediaLibrary::safeName('..\\..\\dossier/a.jpg'));
        $this->assertSame('fichier', MediaLibrary::safeName('../'));
        $this->assertSame('ab.png', MediaLibrary::safeName("a\x00b.png"));
    }
}
