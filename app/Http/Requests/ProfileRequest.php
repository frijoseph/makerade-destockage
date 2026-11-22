<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    use NormalizesPhone;

    /**
     * The route middleware guarantees a signed-in user.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'phone' => ['required', 'string', 'regex:/^\+\d{8,15}$/', Rule::unique('users', 'phone')->ignore($this->user())],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nom', 'phone' => 'téléphone', 'city' => 'ville', 'address' => 'adresse'];
    }
}
