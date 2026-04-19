<?php

namespace App\Http\Requests\Product;

use App\Services\Product\ProductWriteService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuickAddProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create product');
    }

    public function rules(): array
    {
        /** @var ProductWriteService $service */
        $service = app(ProductWriteService::class);

        return $service->quickAddRules();
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

