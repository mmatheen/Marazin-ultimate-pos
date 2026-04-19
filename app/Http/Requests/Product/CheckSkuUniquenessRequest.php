<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckSkuUniquenessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view product');
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $pid = $this->input('product_id');
        if ($pid === '' || $pid === null) {
            $this->merge(['product_id' => null]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
