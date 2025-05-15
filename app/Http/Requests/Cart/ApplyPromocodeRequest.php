<?php

namespace App\Http\Requests\Cart;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;

class ApplyPromocodeRequest extends BaseCartRequest
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
        $promoCodes = config('promo-codes');

        return [
            ...parent::rules(),
            'promocode' => ['required', Rule::in(array_keys($promoCodes))],
        ];
    }
}
