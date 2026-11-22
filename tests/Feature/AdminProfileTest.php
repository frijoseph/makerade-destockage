<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_and_update_their_profile(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Ancien Nom']);

        $this->actingAs($admin)->get(route('admin.profile.edit'))->assertOk()->assertSee('Mon profil')->assertSee('Ancien Nom');
        $this->get(route('admin.dashboard'))->assertSee(route('admin.profile.edit'), false)->assertSee('Déconnexion');

        $this->put(route('admin.profile.update'), ['name' => 'Nouveau Nom', 'email' => 'nouveau@makerade.test'])->assertSessionHasNoErrors();

        $this->assertSame('Nouveau Nom', $admin->fresh()->name);
        $this->assertSame('nouveau@makerade.test', $admin->fresh()->email);
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'pris@makerade.test']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.profile.update'), ['name' => 'X', 'email' => 'pris@makerade.test'])->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'ancien-secret']);

        $this->actingAs($admin)->put(route('admin.profile.password'), ['current_password' => 'faux', 'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret'])->assertSessionHasErrors('current_password');

        $this->put(route('admin.profile.password'), ['current_password' => 'ancien-secret', 'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('nouveau-secret', $admin->fresh()->password));
    }

    public function test_customers_cannot_reach_the_admin_profile(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.profile.edit'))->assertRedirect(route('admin.login'));
    }
}
