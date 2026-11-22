<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use NormalizesPhone;

    /**
     * Anyone can create a customer account.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = mb_strtolower(trim((string) $this->input('email')));
        $this->merge(['email' => $email === '' ? null : $email]);
        $this->normalizePhoneInput();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+\d{8,15}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nom', 'phone' => 'téléphone', 'password' => 'mot de passe', 'terms' => 'conditions'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Un compte existe déjà avec cet e-mail. Connectez-vous ou réinitialisez votre mot de passe.',
            'phone.regex' => 'Numéro de téléphone invalide.',
            'phone.unique' => 'Un compte existe déjà avec ce numéro. Connectez-vous.',
            'terms.accepted' => 'Veuillez accepter les conditions générales.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ];
    }
}
