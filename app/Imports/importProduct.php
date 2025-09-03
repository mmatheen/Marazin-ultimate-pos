<?php

namespace App\Imports;

use App\Models\Unit;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\MainCategory;
use App\Models\StockHistory;
use App\Models\LocationBatch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;


class importProduct implements ToCollection, WithHeadingRow
{
    private $data = [];
    private $validationErrors = []; // Array to store validation errors with row numbers
    private $currentRow = 1; // Track current row number (starting from 1 for header)
    private $importedProducts = []; // Track successfully imported products for rollback

    public function getData()
    {
        return $this->data;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    // Increase max execution time for bulk import
    public function __construct()
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        $this->currentRow = 1; // Initialize row counter
    }

    public function collection(Collection $rows)
    {
        // First pass: Validate all rows without inserting anything
        $this->validateAllRows($rows);
        
        // If there are any validation errors, don't proceed with import
        if (!empty($this->validationErrors)) {
            Log::error("Import cancelled due to validation errors:", $this->validationErrors);
            return; // Stop processing - no data will be imported
        }

        // Second pass: Import all rows (only if validation passed)
        DB::beginTransaction();
        
        try {
            foreach ($rows as $index => $row) {
                $excelRowNumber = $index + 2; // Excel row number (accounting for header)
                $this->processRow($row->toArray(), $excelRowNumber);
            }
            
            DB::commit();
            Log::info("Successfully imported " . count($rows) . " products.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->validationErrors[] = "Import failed: " . $e->getMessage();
            Log::error("Import transaction failed: " . $e->getMessage());
        }
    }

    /**
     * Convert various date formats to Y-m-d format
     */
    private function convertDateFormat($date, $rowNumber)
    {
        if (empty($date)) {
            return null;
        }

        // Remove any extra whitespace
        $date = trim($date);
        
        // If already in correct format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // Try to parse various date formats with exact matching
        $formats = [
            'Y/m/d',    // 2026/05/26
            'Y.m.d',    // 2026.05.09
            'Y-m-d',    // 2026-05-26
            'Y/n/j',    // 2026/5/9 (single digit month/day)
            'Y.n.j',    // 2026.5.9
            'Y-n-j',    // 2026-5-9
        ];

        foreach ($formats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $date);
            if ($dateObj !== false) {
                // Additional validation to ensure the parsed date makes sense
                $reformatted = $dateObj->format($format);
                if ($reformatted === $date) {
                    return $dateObj->format('Y-m-d');
                }
            }
        }

        // Try regex-based parsing for common patterns
        if (preg_match('/^(\d{4})[\/\.-](\d{1,2})[\/\.-](\d{1,2})$/', $date, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            
            // Validate the date components
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // If no format matches, try Carbon parse as last resort
        try {
            $carbonDate = \Carbon\Carbon::parse($date);
            return $carbonDate->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error("Could not parse date '{$date}' in row {$rowNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean row data to handle empty strings and whitespace
     */
    private function cleanRowData(array &$rowArray)
    {
        // Clean numeric fields - convert empty strings or whitespace to null
        $numericFields = [
            'stock_alert_quantity', 
            'alert_quantity',
            'original_price', 
            'retail_price', 
            'whole_sale_price', 
            'special_price', 
            'max_retail_price', 
            'qty', 
            'pax'
        ];

        foreach ($numericFields as $field) {
            if (isset($rowArray[$field])) {
                $value = trim($rowArray[$field]);
                // Only convert empty strings and pure whitespace to null, preserve "0"
                if ($value === '' || $value === ' ' || $value === '  ') {
                    $rowArray[$field] = null;
                } else {
                    $rowArray[$field] = $value;
                }
            }
        }

        // Clean string fields - trim whitespace
        $stringFields = [
            'product_name', 
            'sku', 
            'unit_name', 
            'brand_name', 
            'main_category_name', 
            'sub_category_name',
            'product_image_name',
            'description',
            'batch_no'
        ];

        foreach ($stringFields as $field) {
            if (isset($rowArray[$field])) {
                $rowArray[$field] = trim($rowArray[$field]);
                if ($rowArray[$field] === '') {
                    $rowArray[$field] = null;
                }
            }
        }
    }

    private function validateAllRows(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $excelRowNumber = $index + 2; // Excel row number (accounting for header)
            
            // Skip empty rows during validation
            if (empty(array_filter($rowArray))) {
                continue;
            }

            // Clean and validate data before processing
            $this->cleanRowData($rowArray);

            // Convert expiry date to proper format if present
            if (!empty($rowArray['expiry_date'])) {
                $convertedDate = $this->convertDateFormat($rowArray['expiry_date'], $excelRowNumber);
                if ($convertedDate === false) {
                    $this->validationErrors[] = "Row {$excelRowNumber}: Invalid expiry date format '{$rowArray['expiry_date']}'. Expected formats: YYYY-MM-DD, YYYY/MM/DD, or YYYY.MM.DD";
                    continue;
                }
                $rowArray['expiry_date'] = $convertedDate;
            }

            $validator = Validator::make($rowArray, [
                'sku' => [
                    'nullable',
                    'regex:/^[a-zA-Z0-9\-]+$/',
                    'max:255',
                    Rule::unique('products', 'sku')
                ],
                'product_name' => 'required|string|max:255',
                'unit_name' => 'required|string|max:255',
                'brand_name' => 'nullable|string|max:255',
                'main_category_name' => 'nullable|string|max:255',
                'sub_category_name' => 'nullable|string|max:255',
                'stock_alert_quantity' => 'nullable|numeric|min:0',
                'original_price' => 'required|numeric|min:0',
                'retail_price' => 'required|numeric|min:0',
                'whole_sale_price' => 'required|numeric|min:0',
                'special_price' => 'nullable|numeric|min:0',
                'max_retail_price' => 'nullable|numeric|min:0',
                'qty' => 'nullable|numeric|min:0',
                'batch_no' => 'nullable|string|max:255',
                'expiry_date' => 'nullable|date_format:Y-m-d|after:today',
            ], [
                'sku.unique' => 'The SKU "' . ($rowArray['sku'] ?? 'N/A') . '" already exists. Please provide a unique SKU.',
                'sku.regex' => 'The SKU must contain only letters, numbers, and hyphens.',
                'product_name.required' => 'Product name is required.',
                'unit_name.required' => 'Unit name is required.',
                'stock_alert_quantity.numeric' => 'Stock alert quantity must be a valid number.',
                'original_price.required' => 'Original price is required.',
                'original_price.numeric' => 'Original price must be a valid number.',
                'retail_price.required' => 'Retail price is required.',
                'retail_price.numeric' => 'Retail price must be a valid number.',
                'whole_sale_price.required' => 'Wholesale price is required.',
                'whole_sale_price.numeric' => 'Wholesale price must be a valid number.',
                'expiry_date.date_format' => 'Expiry date must be in Y-m-d format (YYYY-MM-DD).',
                'expiry_date.after' => 'Expiry date must be in the future (after today: ' . date('Y-m-d') . ').',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->validationErrors[] = "Row {$excelRowNumber}: {$error}";
                }
                Log::error("Validation Errors for Row {$excelRowNumber}:", $validator->errors()->toArray());
            }
        }
    }

    private function processRow(array $row, int $excelRowNumber)
    {
        try {
            // Skip empty rows
            if (empty(array_filter($row))) {
                return null;
            }

            // Clean data before processing
            $this->cleanRowData($row);

            // Convert expiry date to proper format if present
            if (!empty($row['expiry_date'])) {
                $convertedDate = $this->convertDateFormat($row['expiry_date'], $excelRowNumber);
                if ($convertedDate !== false) {
                    $row['expiry_date'] = $convertedDate;
                }
            }

            // Generate SKU if not provided
            if (empty($row['sku'])) {
                $lastProduct = Product::whereRaw("sku REGEXP '^[0-9]+$'")->orderBy('id', 'desc')->first();
                if ($lastProduct && is_numeric($lastProduct->sku)) {
                    $lastSkuNumber = (int) $lastProduct->sku;
                } else {
                    $lastSkuNumber = 0;
                }
                $row['sku'] = str_pad($lastSkuNumber + 1, 4, '0', STR_PAD_LEFT);
            }

            // Get authenticated user's first location
            $authUser = auth()->user();
            if (!$authUser) {
                throw new \Exception("User not authenticated.");
            }

            $authLocationId = $authUser->location_id ?? 1;

            // Resolve or create related models
            $unit = Unit::firstOrCreate(
                ['name' => $row['unit_name']], 
                ['location_id' => $authLocationId]
            );
            
            $brand = !empty($row['brand_name']) ? 
                Brand::firstOrCreate(
                    ['name' => $row['brand_name']], 
                    ['location_id' => $authLocationId]
                ) : null;
            
            $mainCategory = !empty($row['main_category_name']) ? 
                MainCategory::firstOrCreate(
                    ['mainCategoryName' => $row['main_category_name']], 
                    ['location_id' => $authLocationId]
                ) : null;
            
            $subCategory = !empty($row['sub_category_name']) && $mainCategory ? 
                SubCategory::firstOrCreate([
                    'subCategoryname' => $row['sub_category_name'],
                    'main_category_id' => $mainCategory->id,
                ], ['location_id' => $authLocationId]) : null;

            // Store data for later use if necessary
            $this->data[] = array_merge($row, ['excel_row' => $excelRowNumber]);

            // Create and save the product
            $product = new Product([
                'product_name' => $row['product_name'],
                'sku' => $row['sku'],
                'unit_id' => $unit->id,
                'brand_id' => $brand ? $brand->id : null,
                'main_category_id' => $mainCategory ? $mainCategory->id : null,
                'sub_category_id' => $subCategory ? $subCategory->id : null,
                'stock_alert' => 1,
                'alert_quantity' => $row['stock_alert_quantity'] ?? null,
                'product_image' => $row['product_image_name'] ?? null,
                'description' => $row['description'] ?? null,
                'is_imei_or_serial_no' => $row['is_imeiserial_no'] ?? null,
                'is_for_selling' => $row['is_for_selling'] ?? null,
                'product_type' => $row['product_type'] ?? null,
                'pax' => $row['pax'] ?? null,
                'original_price' => $row['original_price'],
                'retail_price' => $row['retail_price'],
                'whole_sale_price' => $row['whole_sale_price'],
                'special_price' => $row['special_price'] ?? null,
                'max_retail_price' => $row['max_retail_price'] ?? null,
            ]);
            $product->save();

            $productId = $product->id;

            // Insert into location_product table
            DB::table('location_product')->insert([
                'location_id' => $authLocationId,
                'product_id' => $productId,
                'qty' => 0, // Default quantity
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Handle Batch, LocationBatch, StockHistory if quantity is provided
            if (!empty($row['qty']) && $row['qty'] > 0) {
                $formattedExpiryDate = !empty($row['expiry_date']) ? $row['expiry_date'] : null;

                // Generate batch number if not provided
                $batchNo = $row['batch_no'] ?? Batch::generateNextBatchNo();

                // Create or update batch
                $batch = Batch::updateOrCreate(
                    [
                        'batch_no' => $batchNo,
                        'product_id' => $productId,
                    ],
                    [
                        'qty' => $row['qty'],
                        'unit_cost' => $row['original_price'],
                        'retail_price' => $row['retail_price'],
                        'wholesale_price' => $row['whole_sale_price'],
                        'special_price' => $row['special_price'] ?? 0,
                        'max_retail_price' => $row['max_retail_price'] ?? 0,
                        'expiry_date' => $formattedExpiryDate,
                    ]
                );

                // Create or update location batch
                $locationBatch = LocationBatch::updateOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'location_id' => $authLocationId,
                    ],
                    [
                        'qty' => $row['qty'],
                    ]
                );

                // Create stock history entry
                StockHistory::updateOrCreate(
                    [
                        'loc_batch_id' => $locationBatch->id,
                        'stock_type' => StockHistory::STOCK_TYPE_OPENING,
                    ],
                    [
                        'quantity' => $row['qty'],
                    ]
                );

                // Update location_product quantity
                DB::table('location_product')
                    ->where('location_id', $authLocationId)
                    ->where('product_id', $productId)
                    ->update(['qty' => $row['qty']]);
            }

            return $product;

        } catch (\Exception $e) {
            throw new \Exception("Row {$excelRowNumber}: " . $e->getMessage());
        }
    }
}
