<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Forgotten password: e-mail with a reset link, then a new password.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('pages.auth.forgot');
    }

    /**
     * The answer is identical whether or not the address has an account, so accounts cannot be enumerated.
     */
    public function email(Request $request): RedirectResponse
    {
        $login = trim((string) $request->input('login', $request->input('email')));
        $request->merge(['login' => $login]);
        $request->validate(['login' => ['required', 'string', 'max:255']]);

        $user = User::query()
            ->where(str_contains($login, '@') ? 'email' : 'phone', str_contains($login, '@') ? mb_strtolower($login) : Phone::normalize($login, $request->input('phone_country')))
            ->first();

        // Without an e-mail on the account nothing can be sent: the answer stays the same on purpose.
        if ($user?->email) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', 'Si un compte correspond, un e-mail de réinitialisation vient de partir. Pensez à vérifier vos courriers indésirables. Sans e-mail sur votre compte, contactez-nous.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('pages.auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ], ['password' => 'mot de passe']);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Ce lien est invalide ou a expiré. Demandez-en un nouveau.']);
        }

        return redirect()->route('account')->with('status', 'Mot de passe modifié. Vous pouvez vous connecter.');
    }
}
