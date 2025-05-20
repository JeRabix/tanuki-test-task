<?php

namespace App\Http\Requests\Cart;

use Illuminate\Validation\Rule;
use App\Models\Product\Product;
use App\Services\Cart\DTO\SetCartProductDTO;
use Illuminate\Contracts\Validation\ValidationRule;

class SetCartProductRequest extends BaseCartRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function toDTO(): SetCartProductDTO
    {
        return SetCartProductDTO::from($this->validated());
    }
}
