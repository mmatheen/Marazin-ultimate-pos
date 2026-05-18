<?php

namespace App\Support;

use App\Models\Setting;

class PurchaseTableColumns
{
    public const OPTIONAL_COLUMNS = [
        'free_qty' => [
            'label' => 'Free Qty',
            'requires_free_qty' => true,
        ],
        'claim_free_qty' => [
            'label' => 'Claim Free Qty',
            'requires_free_qty' => true,
        ],
        'discount_percent' => [
            'label' => 'Discount Percent',
        ],
        'unit_cost_after_discount' => [
            'label' => 'Unit Cost (After Discount)',
        ],
        'product_tax' => [
            'label' => 'Product Tax',
        ],
        'tax_amount' => [
            'label' => 'Tax Amount',
        ],
        'special_price' => [
            'label' => 'Special Price',
        ],
        'wholesale_price' => [
            'label' => 'Wholesale Price',
        ],
        'profit_margin' => [
            'label' => 'Profit Margin %',
        ],
        'expiry_date' => [
            'label' => 'Expiry Date',
        ],
        'batch' => [
            'label' => 'Batch',
        ],
    ];

    public const MANDATORY_COLUMNS = [
        'index' => '#',
        'product_name' => 'Product Name',
        'purchase_quantity' => 'Purchase Quantity',
        'unit_cost_before_discount' => 'Unit Cost (Before Discount)',
        'line_total' => 'Line Total (Incl Tax)',
        'max_retail_price' => 'Max Retail Price (MRP)',
        'retail_price' => 'Retail Price',
        'actions' => 'Remove',
    ];

    public static function defaultVisibility(): array
    {
        $defaults = [];
        foreach (array_keys(self::OPTIONAL_COLUMNS) as $key) {
            $defaults[$key] = false;
        }

        return $defaults;
    }

    public static function resolved(?array $stored = null, bool $canUseFreeQty = true, bool $canManageTax = true): array
    {
        $visibility = array_merge(self::defaultVisibility(), is_array($stored) ? $stored : []);

        if (!$canUseFreeQty) {
            $visibility['free_qty'] = false;
            $visibility['claim_free_qty'] = false;
        }

        if (!$canManageTax) {
            $visibility['product_tax'] = false;
            $visibility['tax_amount'] = false;
        }

        foreach ($visibility as $key => $value) {
            $visibility[$key] = (bool) $value;
        }

        return $visibility;
    }

    public static function fromSetting(bool $canUseFreeQty = true, bool $canManageTax = true): array
    {
        $setting = Setting::first();
        $stored = $setting?->purchase_table_columns;

        return self::resolved(is_array($stored) ? $stored : null, $canUseFreeQty, $canManageTax);
    }

    public static function isVisible(string $key, array $visibility): bool
    {
        return !empty($visibility[$key]);
    }

    public static function settingsFormColumns(bool $canUseFreeQty = true, bool $canManageTax = true): array
    {
        return collect(self::OPTIONAL_COLUMNS)
            ->filter(function (array $meta, string $key) use ($canUseFreeQty, $canManageTax) {
                if (!$canManageTax && in_array($key, ['product_tax', 'tax_amount'], true)) {
                    return false;
                }

                if (!empty($meta['requires_free_qty']) && !$canUseFreeQty) {
                    return false;
                }

                return true;
            })
            ->all();
    }
}
