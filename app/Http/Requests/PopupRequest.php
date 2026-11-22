<?php

namespace App\Http\Requests;

use App\Models\Popup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PopupRequest extends FormRequest
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
            'is_active' => $this->boolean('is_active'),
            'coupon_code' => $this->filled('coupon_code') ? mb_strtoupper(preg_replace('/\s+/', '', (string) $this->input('coupon_code')) ?? '') : null,
            'pages' => array_values((array) $this->input('pages', [])),
            'segments' => $this->input('kind') === 'wheel' ? array_values((array) $this->input('segments', [])) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(Popup::KINDS))],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:1000'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'coupon_code' => ['nullable', 'string', 'max:40', Rule::exists('coupons', 'code')],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/|#|tel:|mailto:)/'],
            'trigger' => ['required', Rule::in(array_keys(Popup::TRIGGERS))],
            'trigger_value' => ['required', 'integer', 'min:0', 'max:3600'],
            'frequency' => ['required', Rule::in(array_keys(Popup::FREQUENCIES))],
            'pages' => ['array'],
            'pages.*' => [Rule::in(array_merge(['all'], array_keys(Popup::PAGES)))],
            'devices' => ['required', Rule::in(['all', 'mobile', 'desktop'])],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'segments' => [Rule::requiredIf(fn (): bool => $this->input('kind') === 'wheel'), 'nullable', 'array', 'min:2', 'max:12'],
            'segments.*.label' => ['required', 'string', 'max:40'],
            'segments.*.coupon_code' => ['nullable', 'string', 'max:40'],
            'segments.*.weight' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'segments.*.color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom interne', 'kind' => 'type', 'title' => 'titre', 'body' => 'texte', 'coupon_code' => 'code promo',
            'button_label' => 'bouton', 'trigger_value' => 'valeur du déclencheur', 'frequency' => 'fréquence',
            'segments' => 'secteurs de la roue', 'segments.*.label' => 'libellé du secteur', 'segments.*.weight' => 'poids du secteur',
            'starts_at' => 'début', 'ends_at' => 'fin',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coupon_code.exists' => 'Ce code promo n’existe pas : créez-le d’abord dans Promotions.',
            'segments.min' => 'Une roue a besoin d’au moins 2 secteurs.',
            'ends_at.after' => 'La fin doit être postérieure au début.',
        ];
    }
}
