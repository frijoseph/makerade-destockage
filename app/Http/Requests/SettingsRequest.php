<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    /**
     * Access is already restricted to administrators by the route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shop_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'regex:/^\+?\d{8,15}$/'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'currency_position' => ['required', 'in:before,after'],
            'currency_decimals' => ['required', 'integer', 'in:0,2'],
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['whatsapp_number.regex' => 'Numéro WhatsApp au format international, chiffres uniquement (ex. 2250700000000).'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'shop_name' => 'nom de la boutique',
            'currency_symbol' => 'symbole monétaire',
            'shipping_fee' => 'frais de livraison',
            'free_shipping_threshold' => 'seuil de livraison gratuite',
        ];
    }
}
