<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Customer;
use App\Models\Order;
use App\OrderStatus;
use App\Support\AccountLinker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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

        $orders = Order::query()->where('user_id', $user->id)->withSum('items as units', 'quantity')->latest('placed_at')->get();
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

        $user->update(['name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone']]);

        // The customer record of this phone is only touched when it already belongs to the account
        // (or is brand new): a changed phone number never grants access to someone else's history.
        $customer = Customer::query()->firstOrCreate(['phone' => $data['phone']], ['name' => $data['name']]);

        if (AccountLinker::linkIfSafe($user, $customer)) {
            $customer->update(['name' => $data['name'], 'email' => $user->email, 'city' => $data['city'] ?? null, 'address' => $data['address'] ?? null]);
        }

        return back()->with('status', 'Vos informations sont à jour.');
    }

    /**
     * Brings orders placed before the account existed (or as a guest) into the account.
     * Proof of possession: the order number and the amount paid, for an order placed with the account's phone.
     */
    public function claim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'total' => ['required', 'string', 'max:20'],
        ], ['number.required' => 'Saisissez le numéro de la commande.', 'total.required' => 'Saisissez le montant payé.']);

        $key = 'claim-order:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['number' => 'Trop de tentatives. Réessayez dans '.ceil(RateLimiter::availableIn($key) / 60).' minute(s).'], 'claim');
        }

        $count = AccountLinker::claim($request->user(), $data['number'], $data['total']);

        if ($count === 0) {
            RateLimiter::hit($key, 3600);

            return back()->withErrors(['number' => 'Aucune commande ne correspond : vérifiez le numéro, le montant payé et que la commande a été passée avec le téléphone de votre compte.'], 'claim');
        }

        RateLimiter::clear($key);

        return back()->with('status', $count.' commande(s) ajoutée(s) à votre compte.');
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
