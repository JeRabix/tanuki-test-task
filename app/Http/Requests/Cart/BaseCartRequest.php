<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class BaseCartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fingerprint' => ['required', 'string'],
        ];
    }

    public function getFingerprint(): string
    {
        $fingerprint = $this->input('fingerprint');

        if (!$fingerprint) {
            throw ValidationException::withMessages([
                'fingerprint' => 'fingerprint is required.',
            ]);
        }

        return $fingerprint;
    }
}
