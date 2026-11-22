<?php

namespace App\Http\Requests;

use App\StockMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    /**
     * Manual movements allowed from the stock screen (sales and returns come from orders).
     *
     * @var list<StockMovementType>
     */
    public const MANUAL_TYPES = [StockMovementType::Restock, StockMovementType::Adjustment, StockMovementType::Loss];

    /**
     * Access is already restricted by the admin middleware.
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
            'product_id' => ['required', 'regex:/^\d+(:\d+)?$/'],
            'type' => ['required', Rule::enum(StockMovementType::class)->only(self::MANUAL_TYPES)],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['product_id' => 'produit', 'type' => 'type de mouvement', 'quantity' => 'quantité', 'reason' => 'motif'];
    }
}
