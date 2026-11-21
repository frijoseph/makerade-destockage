<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
            'reviews' => (int) $this->input('reviews', 0),
            'slug' => Str::slug($this->input('slug') ?: $this->input('name').' '.$this->input('variant')),
            'is_new' => $this->boolean('is_new'),
            'is_hot' => $this->boolean('is_hot'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $isCreating = ! $product instanceof Product;

        return [
            'name' => ['required', 'string', 'max:255'],
            'variant' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($product)],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'old_price' => ['nullable', 'numeric', 'gt:price', 'max:9999999999'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'stock' => [$isCreating ? 'required' : 'prohibited', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'kind' => ['required', Rule::in(Product::KINDS)],
            'c1' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'c2' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'label' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'reviews' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
            'is_new' => ['boolean'],
            'is_hot' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'old_price.gt' => 'Le prix barré doit être supérieur au prix de vente.',
            'stock.prohibited' => 'Le stock se modifie depuis l’écran Stock (mouvement tracé).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'variant' => 'format',
            'sku' => 'référence',
            'category_id' => 'catégorie',
            'brand_id' => 'marque',
            'price' => 'prix',
            'old_price' => 'prix barré',
            'cost_price' => 'prix d’achat',
            'low_stock_threshold' => 'seuil d’alerte',
            'kind' => 'forme du visuel',
            'c1' => 'couleur principale',
            'c2' => 'couleur secondaire',
        ];
    }
}
