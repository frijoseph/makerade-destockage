<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $variants = collect($this->input('variants', []))
            ->filter(fn ($row): bool => is_array($row) && (filled($row['id'] ?? null) || filled($row['option1'] ?? null) || filled($row['option2'] ?? null) || filled($row['option3'] ?? null)))
            ->values()
            ->all();

        $this->merge([
            'variants' => $variants,
            'option_names' => array_values(array_filter(array_map(fn ($name): string => trim((string) $name), (array) $this->input('option_names', [])))),
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
        $maxKb = max(1, (int) Setting::get('media_max_upload_mb')) * 1024;

        return [
            'name' => ['required', 'string', 'max:255'],
            'variant' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($product)],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')],
            'description' => ['nullable', 'string', 'max:30000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'old_price' => ['nullable', 'numeric', 'gt:price', 'max:9999999999'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'stock' => [$isCreating ? 'nullable' : 'prohibited', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'kind' => ['required', Rule::in(Product::KINDS)],
            'c1' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'c2' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'label' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'reviews' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
            'image_media_id' => ['nullable', 'integer', Rule::exists('media_files', 'id')],
            'gallery_media_ids' => ['nullable', 'array', 'max:12'],
            'gallery_media_ids.*' => ['integer', Rule::exists('media_files', 'id')],
            'remove_image' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array', 'max:10'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
            'remove_gallery' => ['nullable', 'array'],
            'remove_gallery.*' => ['integer'],
            'promote_image' => ['nullable', 'integer'],
            'option_names' => ['nullable', 'array', 'max:3'],
            'option_names.*' => ['string', 'max:40'],
            'variants' => ['nullable', 'array', 'max:100'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.option1' => ['nullable', 'string', 'max:60'],
            'variants.*.option2' => ['nullable', 'string', 'max:60'],
            'variants.*.option3' => ['nullable', 'string', 'max:60'],
            'variants.*.sku' => ['nullable', 'string', 'max:60'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'variants.*.old_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
            'variants.*.remove_image' => ['nullable', 'boolean'],
            'is_new' => ['boolean'],
            'is_hot' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Each variant needs at least one option, a unique combination and a unique reference.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $seen = [];
            $skus = [];

            foreach ((array) $this->input('variants', []) as $index => $row) {
                $label = ProductVariant::labelFor([$row['option1'] ?? null, $row['option2'] ?? null, $row['option3'] ?? null]);

                if ($label === '') {
                    $validator->errors()->add("variants.{$index}", 'Chaque variante doit avoir au moins une option (ligne '.($index + 1).').');

                    continue;
                }

                $key = mb_strtolower($label);
                if (isset($seen[$key])) {
                    $validator->errors()->add("variants.{$index}", "La variante « {$label} » apparaît deux fois.");
                }
                $seen[$key] = true;

                $sku = trim((string) ($row['sku'] ?? ''));
                if ($sku === '') {
                    continue;
                }

                $taken = ProductVariant::query()->where('sku', $sku)->when(filled($row['id'] ?? null), fn ($query) => $query->where('id', '!=', $row['id']))->exists()
                    || isset($skus[mb_strtolower($sku)]);
                if ($taken) {
                    $validator->errors()->add("variants.{$index}", "La référence « {$sku} » est déjà utilisée.");
                }
                $skus[mb_strtolower($sku)] = true;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'old_price.gt' => 'Le prix barré doit être supérieur au prix de vente.',
            'gallery.max' => 'Dix photos secondaires au maximum par envoi.',
            'gallery.*.image' => 'Chaque photo secondaire doit être une image.',
            'gallery.*.max' => 'Chaque photo secondaire fait '.(int) Setting::get('media_max_upload_mb').' Mo au maximum.',
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
