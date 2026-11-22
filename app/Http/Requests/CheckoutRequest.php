<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\DeliveryZone;
use App\Models\Setting;
use App\PaymentMethod;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    use NormalizesPhone;

    /**
     * Anyone can order from the storefront.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneInput();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+\d{8,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:80', function (string $attribute, mixed $value, Closure $fail): void {
                if (Setting::get('restrict_to_zones') && DeliveryZone::query()->active()->exists() && ! DeliveryZone::forCity((string) $value)) {
                    $fail('Nous ne livrons pas encore dans cette ville. Choisissez une ville de la liste.');
                }
            }],
            'address' => ['required', 'string', 'max:255'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class), Rule::in((array) Setting::get('payment_methods'))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Votre panier est vide.',
            'phone.regex' => 'Numéro de téléphone invalide.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'phone' => 'téléphone',
            'city' => 'ville',
            'address' => 'adresse',
            'payment_method' => 'moyen de paiement',
        ];
    }
}
