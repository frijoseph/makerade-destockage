<?php

namespace App\Http\Requests;

use App\Models\Brand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
            'slug' => Str::slug((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'position' => (int) $this->input('position', 0),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($this->route('brand'))],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'style' => ['required', Rule::in(Brand::STYLES)],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'position' => ['integer', 'min:0', 'max:100000'],
            'url' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/)/'],
            'logo_file' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['slug.unique' => 'Une marque porte déjà ce nom.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nom', 'color' => 'couleur', 'style' => 'style du logo'];
    }
}
