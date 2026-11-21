<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Si un compte existe avec cette adresse, un e-mail de réinitialisation vient de partir. Pensez à vérifier vos courriers indésirables.');
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
