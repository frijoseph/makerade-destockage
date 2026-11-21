<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryZoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('delivery_zones', 'name')->ignore($this->route('zone'))],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'free_threshold' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'delay' => ['nullable', 'string', 'max:80'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'ville ou zone', 'fee' => 'frais de livraison', 'free_threshold' => 'seuil de livraison offerte', 'delay' => 'délai', 'position' => 'ordre'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => 'Cette ville est déjà dans le tableau.'];
    }
}
