<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use App\Support\AccountLinker;
use App\Support\Phone;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign-up, sign-in and sign-out of storefront customers.
 */
class CustomerAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $request->merge(['login' => trim((string) $request->input('login', $request->input('email')))]);

        $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = (string) $request->input('login');
        $identity = str_contains($login, '@')
            ? ['email' => mb_strtolower($login)]
            : ['phone' => Phone::normalize($login, $request->input('phone_country')) ?? $login];

        $lockKey = 'customer-login:'.Str::lower(implode('', $identity));

        if (RateLimiter::tooManyAttempts($lockKey, 8)) {
            throw ValidationException::withMessages(['login' => 'Trop de tentatives. Réessayez dans '.ceil(RateLimiter::availableIn($lockKey) / 60).' minute(s).'])->errorBag('login');
        }

        if (! Auth::attempt($identity + ['password' => $request->input('password'), 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($lockKey, 900);

            throw ValidationException::withMessages(['login' => 'Téléphone, e-mail ou mot de passe incorrect.'])->errorBag('login');
        }

        RateLimiter::clear($lockKey);

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('account'))->with('status', 'Content de vous revoir, '.explode(' ', $request->user()->name)[0].' !');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        // The phone number is not verified: an existing customer with past orders is NOT attached
        // automatically (its history would be exposed). The member claims those orders from the account page.
        $customer = Customer::query()->firstOrCreate(['phone' => $data['phone']], array_filter(['name' => $data['name'], 'email' => $user->email]));
        AccountLinker::linkIfSafe($user, $customer);

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = redirect()->route('account')->with('status', 'Bienvenue, '.explode(' ', $user->name)[0].' ! Votre compte est créé.');
        $welcome = Coupon::query()->usable()->where('code', Coupon::normalize((string) Setting::get('welcome_coupon_code')))->first();

        return $welcome ? $redirect->with('welcome_code', ['code' => $welcome->code, 'benefit' => $welcome->benefit()]) : $redirect;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Vous êtes déconnecté. À bientôt !');
    }
}
