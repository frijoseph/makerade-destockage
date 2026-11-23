<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Awa Koné', 'role' => 'customer', 'email' => 'awa@makerade.test', 'phone' => '+2250700000001',
            'password' => 'secret-1234', 'is_active' => true, 'city' => 'Abidjan', 'address' => 'Cocody, rue 12',
        ], $overrides);
    }

    public function test_admins_are_sent_to_the_back_office_from_the_account_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('account'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs(User::factory()->manager()->create())->get(route('account'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_customers_stay_on_the_account_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Customer]))->get(route('account'))->assertOk();
    }

    public function test_anyone_who_signs_up_becomes_a_customer_even_when_asking_for_another_role(): void
    {
        $this->post(route('account.register'), [
            'name' => 'Pirate', 'phone' => '+2250700000009', 'password' => 'secret-1234', 'password_confirmation' => 'secret-1234',
            'terms' => '1', 'role' => 'admin', 'is_active' => '0',
        ])->assertSessionHasNoErrors();

        $user = User::query()->where('phone', '+2250700000009')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->canAccessAdmin());
    }

    public function test_admin_creates_a_user_with_a_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('admin.users.store'), $this->payload())->assertCreated();

        $user = User::query()->where('email', 'awa@makerade.test')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue(password_verify('secret-1234', $user->password));

        $profile = Customer::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Abidjan', $profile->city);
        $this->assertSame('Cocody, rue 12', $profile->address);
    }

    public function test_admin_can_create_a_manager_who_then_signs_in_to_the_back_office(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.users.store'), $this->payload(['role' => 'manager', 'phone' => null, 'email' => 'gest@makerade.test']))
            ->assertCreated();

        $manager = User::query()->where('email', 'gest@makerade.test')->firstOrFail();
        $this->assertTrue($manager->canAccessAdmin());
        $this->assertFalse($manager->isAdmin());
    }

    public function test_staff_need_an_email_and_customers_need_a_phone(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->postJson(route('admin.users.store'), $this->payload(['role' => 'admin', 'email' => null]))->assertJsonValidationErrors('email');
        $this->postJson(route('admin.users.store'), $this->payload(['role' => 'customer', 'phone' => null]))->assertJsonValidationErrors('phone');
        $this->postJson(route('admin.users.store'), $this->payload(['password' => 'court']))->assertJsonValidationErrors('password');
    }

    public function test_email_and_phone_stay_unique(): void
    {
        User::factory()->create(['email' => 'awa@makerade.test', 'phone' => '+2250700000001']);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.users.store'), $this->payload())
            ->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_update_changes_the_profile_and_keeps_the_password_when_left_blank(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+2250700000001', 'password' => 'ancien-secret']);

        $this->actingAs($admin)->putJson(route('admin.users.update', $user), $this->payload(['password' => null, 'name' => 'Nouveau Nom', 'city' => 'Bouaké']))->assertOk();

        $user->refresh();
        $this->assertSame('Nouveau Nom', $user->name);
        $this->assertTrue(password_verify('ancien-secret', $user->password));
        $this->assertSame('Bouaké', Customer::query()->where('user_id', $user->id)->value('city'));

        $this->putJson(route('admin.users.update', $user), $this->payload(['password' => 'nouveau-secret']))->assertOk();
        $this->assertTrue(password_verify('nouveau-secret', $user->fresh()->password));
    }

    public function test_a_deactivated_customer_cannot_sign_in(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer, 'phone' => '+2250700000001', 'password' => 'secret-1234']);

        $this->actingAs(User::factory()->admin()->create())->putJson(route('admin.users.update', $user), $this->payload(['is_active' => false]))->assertOk();
        auth()->logout();

        $this->post(route('account.login'), ['login' => '+2250700000001', 'password' => 'secret-1234'])->assertSessionHasErrors('login', errorBag: 'login');
    }

    public function test_an_admin_cannot_demote_deactivate_or_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->putJson(route('admin.users.update', $admin), $this->payload(['role' => 'manager', 'phone' => null, 'email' => $admin->email]))->assertJsonValidationErrors('role');
        $this->putJson(route('admin.users.update', $admin), $this->payload(['role' => 'admin', 'is_active' => false, 'phone' => null, 'email' => $admin->email]))->assertJsonValidationErrors('role');
        $this->deleteJson(route('admin.users.destroy', $admin))->assertStatus(422);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_the_last_active_admin_cannot_be_removed(): void
    {
        $actor = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $actor->update(['is_active' => true]);
        $this->actingAs($actor);

        $this->deleteJson(route('admin.users.destroy', $other))->assertOk();

        $solo = User::factory()->admin()->create(['is_active' => false]);
        $this->assertSame(1, User::query()->where('role', UserRole::Admin)->where('is_active', true)->count());
        $this->deleteJson(route('admin.users.destroy', $solo))->assertOk();
    }

    public function test_a_user_with_orders_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);
        Order::factory()->create(['user_id' => $user->id]);

        $this->actingAs(User::factory()->admin()->create())->deleteJson(route('admin.users.destroy', $user))->assertStatus(422);
        $this->assertModelExists($user);

        $free = User::factory()->create(['role' => UserRole::Customer]);
        $this->deleteJson(route('admin.users.destroy', $free))->assertOk();
        $this->assertModelMissing($free);
    }

    public function test_the_page_lists_users_and_is_reserved_to_administrators(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer, 'name' => 'Fatou Traoré']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.users.index'))->assertOk()->assertSee('Fatou Traoré')->assertSee('Utilisateurs');
        $this->get(route('admin.users.index', ['role' => 'customer', 'q' => 'Fatou']))->assertOk()->assertSee('Fatou Traoré');

        $this->actingAs(User::factory()->manager()->create())->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.users.store'), $this->payload())->assertRedirect(route('admin.login'));
    }
}
