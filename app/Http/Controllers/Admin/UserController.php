<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Customer;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Accounts of the shop (administrators, managers and customers), reserved to administrators.
 * The customer profile (city, address) lives in the linked customer record, found through the phone number.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount('orders')
            ->when($request->query('q'), fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when(UserRole::tryFrom((string) $request->query('role')), fn ($query, $role) => $query->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'profiles' => Customer::query()->whereIn('user_id', $users->pluck('id'))->get()->keyBy('user_id'),
            'roles' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])->all(),
            'counts' => User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $this->syncProfile($user, $data);

        return response()->json(['message' => "Utilisateur « {$user->name} » créé."], 201);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $active = (bool) ($data['is_active'] ?? false);
        $staysAdmin = $data['role'] === UserRole::Admin->value && $active;

        if ($user->is($request->user()) && ! $staysAdmin) {
            throw ValidationException::withMessages(['role' => 'Vous ne pouvez pas retirer vos propres droits ni désactiver votre compte.']);
        }

        if ($user->isAdmin() && ! $staysAdmin && $this->otherActiveAdmins($user) === 0) {
            throw ValidationException::withMessages(['role' => 'Il doit rester au moins un administrateur actif.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => $active,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $this->syncProfile($user, $data);

        return response()->json(['message' => "Utilisateur « {$user->name} » mis à jour."]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        if ($user->isAdmin() && $this->otherActiveAdmins($user) === 0) {
            return response()->json(['message' => 'Il doit rester au moins un administrateur actif.'], 422);
        }

        if ($user->orders()->exists()) {
            return response()->json(['message' => 'Cet utilisateur a des commandes : désactivez son compte plutôt que de le supprimer.'], 422);
        }

        $user->delete();

        return response()->json(['message' => "Utilisateur « {$user->name} » supprimé."]);
    }

    private function otherActiveAdmins(User $user): int
    {
        return User::query()->where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($user->id)->count();
    }

    /**
     * Keeps the customer record of the account (name, e-mail, city, address) in step with the form.
     * A customer record that already belongs to someone else is left alone.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncProfile(User $user, array $data): void
    {
        $customer = Customer::query()->where('user_id', $user->id)->first();

        if (! $customer && $user->phone) {
            $customer = Customer::query()->firstOrCreate(['phone' => $user->phone], ['name' => $user->name]);

            if ($customer->user_id !== null && $customer->user_id !== $user->id) {
                return;
            }
        }

        if (! $customer) {
            return;
        }

        $customer->update([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone ?? $customer->phone,
            'email' => $user->email,
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }
}
