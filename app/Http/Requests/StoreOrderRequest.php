<?php

namespace App\Http\Requests;

use App\OrderChannel;
use App\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Access is already restricted by the admin middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Drops empty rows left by the order form before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => collect($this->input('items', []))
                ->filter(fn ($item): bool => is_array($item) && ! empty($item['product_id']))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:30'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.city' => ['nullable', 'string', 'max:80'],
            'customer.address' => ['nullable', 'string', 'max:255'],
            'channel' => ['required', Rule::enum(OrderChannel::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Ajoutez au moins un produit.',
            'items.*.product_id.distinct' => 'Un produit apparaît deux fois : regroupez les quantités.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer.name' => 'nom du client',
            'customer.phone' => 'téléphone',
            'customer.email' => 'e-mail',
            'customer.city' => 'ville',
            'customer.address' => 'adresse',
            'payment_method' => 'moyen de paiement',
            'channel' => 'canal',
            'discount' => 'remise',
            'items.*.quantity' => 'quantité',
        ];
    }
}
