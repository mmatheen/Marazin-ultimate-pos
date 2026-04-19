<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateBatchPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('edit batch prices');
    }

    public function rules(): array
    {
        return [
            'batches' => 'required|array|min:1',
            'batches.*.id' => 'required|exists:batches,id',
            'batches.*.unit_cost' => 'required|numeric|min:0',
            'batches.*.wholesale_price' => 'required|numeric|min:0',
            'batches.*.special_price' => 'required|numeric|min:0',
            'batches.*.retail_price' => 'required|numeric|min:0',
            'batches.*.max_retail_price' => 'required|numeric|min:0',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 400,
                'errors' => $validator->errors(),
            ])
        );
    }
}

