<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Customer;
use App\Models\Order;
use App\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Customer area: login/sign-up forms for visitors, dashboard with orders and profile for members.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        if (! $user) {
            return view('pages.account', ['member' => null]);
        }

        $customerIds = Customer::query()->where('user_id', $user->id)->pluck('id');
        $orders = Order::query()->whereIn('customer_id', $customerIds)->withSum('items as units', 'quantity')->latest('placed_at')->get();
        $profile = Customer::query()->where('user_id', $user->id)->latest('updated_at')->first();

        return view('pages.account', [
            'member' => $user,
            'orders' => $orders,
            'profile' => $profile,
            'spent' => (float) $orders->filter(fn (Order $order): bool => in_array($order->status, OrderStatus::billable(), true))->sum('total'),
        ]);
    }

    public function profile(ProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $user->update(['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'phone' => $data['phone']]);

        Customer::query()->updateOrCreate(
            ['phone' => Customer::normalizePhone($data['phone'])],
            ['user_id' => $user->id, 'name' => $data['name'], 'email' => $user->email, 'city' => $data['city'] ?? null, 'address' => $data['address'] ?? null],
        );

        return back()->with('status', 'Vos informations sont à jour.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ], ['current_password' => 'mot de passe actuel', 'password' => 'nouveau mot de passe']);

        $request->user()->update(['password' => Hash::make($request->string('password')->toString())]);

        return back()->with('status', 'Mot de passe modifié.');
    }
}
