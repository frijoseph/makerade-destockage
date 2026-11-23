<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    use NormalizesPhone;

    /**
     * Access is already restricted to administrators by the route middleware.
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
        $user = $this->route('user');
        $isCustomer = $this->input('role') === UserRole::Customer->value;

        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'email' => [Rule::requiredIf(! $isCustomer), 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => [Rule::requiredIf($isCustomer), 'nullable', 'string', 'regex:/^\+\d{8,15}$/', Rule::unique('users', 'phone')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', Password::min(8)],
            'is_active' => ['boolean'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nom', 'role' => 'rôle', 'email' => 'e-mail', 'phone' => 'téléphone', 'password' => 'mot de passe', 'city' => 'ville', 'address' => 'adresse'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L’e-mail est obligatoire pour un administrateur ou un gestionnaire : il sert à se connecter.',
            'email.unique' => 'Un utilisateur existe déjà avec cet e-mail.',
            'phone.required' => 'Le téléphone est obligatoire pour un client : il sert à se connecter.',
            'phone.regex' => 'Numéro de téléphone invalide (ex. +2250700000000).',
            'phone.unique' => 'Un utilisateur existe déjà avec ce numéro.',
        ];
    }
}
