<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\ValueObjects\ReceiptConfig;

class UpdateReceiptConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to update location settings
        return true; // Add your authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ReceiptConfig::validationRules();
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'spacing_mode.required' => 'Please select a spacing mode.',
            'spacing_mode.in' => 'Invalid spacing mode selected.',
            'font_size_base.required' => 'Font size is required.',
            'font_size_base.integer' => 'Font size must be a number.',
            'font_size_base.min' => 'Font size must be at least ' . ReceiptConfig::FONT_SIZE_MIN . 'px.',
            'font_size_base.max' => 'Font size cannot exceed ' . ReceiptConfig::FONT_SIZE_MAX . 'px.',
            'line_spacing.required' => 'Line spacing is required.',
            'line_spacing.integer' => 'Line spacing must be a number.',
            'line_spacing.min' => 'Line spacing must be at least ' . ReceiptConfig::LINE_SPACING_MIN . '.',
            'line_spacing.max' => 'Line spacing cannot exceed ' . ReceiptConfig::LINE_SPACING_MAX . '.',
            'font_family.in' => 'Invalid font family selected.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean for checkboxes
        $booleanFields = [
            'show_logo',
            'show_customer_phone',
            'show_mrp_strikethrough',
            'show_imei',
            'show_discount_breakdown',
            'show_payment_method',
            'show_outstanding_due',
            'show_stats_section',
            'show_footer_note',
        ];

        $data = $this->all();

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            } else {
                $data[$field] = false;
            }
        }

        $this->merge($data);
    }
}
