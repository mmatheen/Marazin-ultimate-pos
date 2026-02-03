<?php

namespace App\ValueObjects;

class ReceiptConfig
{
    // Spacing mode constants
    public const SPACING_COMPACT = 'compact';
    public const SPACING_SPACIOUS = 'spacious';

    // Font size limits
    public const FONT_SIZE_MIN = 9;
    public const FONT_SIZE_MAX = 14;

    // Line spacing limits
    public const LINE_SPACING_MIN = 1;
    public const LINE_SPACING_MAX = 10;

    /**
     * Get default receipt configuration
     *
     * @return array
     */
    public static function defaults(): array
    {
        return [
            // Display options
            'show_logo' => true,
            'show_customer_phone' => true,
            'show_mrp_strikethrough' => true,
            'show_imei' => true,
            'show_discount_breakdown' => true,
            'show_payment_method' => true,
            'show_outstanding_due' => true,
            'show_stats_section' => true,
            'show_footer_note' => true,

            // Layout settings
            'spacing_mode' => self::SPACING_COMPACT,
            'font_size_base' => 11,
            'line_spacing' => 5,
        ];
    }

    /**
     * Merge user config with defaults
     *
     * @param array|null $config
     * @return array
     */
    public static function merge(?array $config): array
    {
        if (empty($config)) {
            return self::defaults();
        }

        return array_merge(self::defaults(), $config);
    }

    /**
     * Get validation rules for receipt config
     *
     * @return array
     */
    public static function validationRules(): array
    {
        return [
            'show_logo' => 'boolean',
            'show_customer_phone' => 'boolean',
            'show_mrp_strikethrough' => 'boolean',
            'show_imei' => 'boolean',
            'show_discount_breakdown' => 'boolean',
            'show_payment_method' => 'boolean',
            'show_outstanding_due' => 'boolean',
            'show_stats_section' => 'boolean',
            'show_footer_note' => 'boolean',
            'spacing_mode' => 'required|in:' . self::SPACING_COMPACT . ',' . self::SPACING_SPACIOUS,
            'font_size_base' => 'required|integer|min:' . self::FONT_SIZE_MIN . '|max:' . self::FONT_SIZE_MAX,
            'line_spacing' => 'required|integer|min:' . self::LINE_SPACING_MIN . '|max:' . self::LINE_SPACING_MAX,
        ];
    }

    /**
     * Validate receipt configuration
     *
     * @param array $config
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function validate(array $config): bool
    {
        $validator = \Validator::make($config, self::validationRules());

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid receipt configuration: ' . $validator->errors()->first());
        }

        return true;
    }

    /**
     * Get available spacing modes
     *
     * @return array
     */
    public static function getSpacingModes(): array
    {
        return [
            self::SPACING_COMPACT => 'Compact (Shrunk - Fast Print)',
            self::SPACING_SPACIOUS => 'Spacious (Comfortable Reading)',
        ];
    }

    /**
     * Get preset configurations
     *
     * @return array
     */
    public static function getPresets(): array
    {
        return [
            'minimal' => [
                'name' => 'Minimal (Fast Print)',
                'config' => [
                    'show_logo' => false,
                    'show_customer_phone' => false,
                    'show_mrp_strikethrough' => false,
                    'show_imei' => true,
                    'show_discount_breakdown' => false,
                    'show_payment_method' => false,
                    'show_outstanding_due' => false,
                    'show_stats_section' => false,
                    'show_footer_note' => false,
                    'spacing_mode' => self::SPACING_COMPACT,
                    'font_size_base' => 10,
                    'line_spacing' => 3,
                ],
            ],
            'standard' => [
                'name' => 'Standard (Balanced)',
                'config' => self::defaults(),
            ],
            'detailed' => [
                'name' => 'Detailed (Full Information)',
                'config' => [
                    'show_logo' => true,
                    'show_customer_phone' => true,
                    'show_mrp_strikethrough' => true,
                    'show_imei' => true,
                    'show_discount_breakdown' => true,
                    'show_payment_method' => true,
                    'show_outstanding_due' => true,
                    'show_stats_section' => true,
                    'show_footer_note' => true,
                    'spacing_mode' => self::SPACING_SPACIOUS,
                    'font_size_base' => 12,
                    'line_spacing' => 7,
                ],
            ],
        ];
    }
}
