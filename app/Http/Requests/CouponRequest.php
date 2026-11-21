<?php

namespace App\Http\Requests;

use App\CouponType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    /**
     * Access is already restricted by the admin middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(preg_replace('/\s+/', '', (string) $this->input('code')) ?? ''),
            'min_subtotal' => $this->input('min_subtotal') ?: 0,
            'value' => $this->input('value') ?: 0,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPercent = $this->input('type') === CouponType::Percent->value;

        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($this->route('coupon'))],
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'numeric', 'min:0', $isPercent ? 'max:100' : 'max:9999999999'],
            'min_subtotal' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'once_per_customer' => ['boolean'],
            'auto_apply' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'code', 'label' => 'description', 'value' => 'valeur', 'min_subtotal' => 'achat minimum',
            'max_discount' => 'remise maximale', 'usage_limit' => 'limite d’utilisation', 'starts_at' => 'début', 'ends_at' => 'fin',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Le code ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'code.unique' => 'Ce code existe déjà.',
            'ends_at.after' => 'La fin doit être postérieure au début.',
        ];
    }
}
