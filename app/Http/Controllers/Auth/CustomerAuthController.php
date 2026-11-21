<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Sign-up, sign-in and sign-out of storefront customers.
 */
class CustomerAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou mot de passe incorrect.'])->errorBag('login');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('account'))->with('status', 'Content de vous revoir, '.explode(' ', $request->user()->name)[0].' !');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        // Orders placed before the account existed are found again through the phone number.
        Customer::query()->updateOrCreate(
            ['phone' => Customer::normalizePhone($data['phone'])],
            ['user_id' => $user->id, 'name' => $data['name'], 'email' => $user->email],
        );

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
