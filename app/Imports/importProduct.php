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
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class importProduct implements ToModel, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures; // This allows skipping failed rows

    private $data = [];
    private $validationErrors = []; // Array to store validation errors

    public function getData()
    {
        return $this->data;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    public function model(array $row)
    {
        DB::beginTransaction(); // Begin the transaction

        try {
            $validator = Validator::make($row, [

                'sku' => [
                    'nullable', // Allow SKU to be nullable
                    'string',   // Ensure SKU is always treated as a string
                    'max:255',  // Maximum length for SKU
                    Rule::unique('products', 'sku') // Simply ensure the SKU is unique
                ],

                'product_name' => 'required|string',
                'unit_name' => 'required|string',
                'brand_name' => 'required|string',
                'main_category_name' => 'required|string',
                'sub_category_name' => 'required|string',
            ], [
                // Custom error messages for SKU validation
                'sku.unique' => 'The SKU "' . $row['sku'] . '" already exists. Please provide a unique SKU.',
            ]
        );


            // If validation fails, store the error messages
            if ($validator->fails()) {
                // Store all error messages for this row
                $this->validationErrors[] = $validator->errors()->all();
                Log::error('Validation Errors for SKU:', $validator->errors()->toArray());

                DB::rollBack(); // Roll back the transaction on validation failure
                return null;
            }

            // **Process the SKU if it's not provided**
            if (empty($row['sku'])) {
                $lastProduct = Product::where('sku', 'LIKE', 'SKU%')->orderBy('id', 'desc')->first();
                $lastSkuNumber = $lastProduct ? (int) filter_var($lastProduct->sku, FILTER_SANITIZE_NUMBER_INT) : 0;
                $row['sku'] = 'SKU' . str_pad($lastSkuNumber + 1, 4, '0', STR_PAD_LEFT); // Format SKU as 'SKU0001', 'SKU0002', etc.
            }

            // **Resolve Unit, Brand, and Category**
            $authId = auth()->id(); // Get current authenticated user's ID
            $unit = Unit::firstOrCreate(['name' => $row['unit_name']], ['location_id' => $authId]);
            $brand = Brand::firstOrCreate(['name' => $row['brand_name']], ['location_id' => $authId]);
            $mainCategory = MainCategory::firstOrCreate(['mainCategoryName' => $row['main_category_name']], ['location_id' => $authId]);
            $subCategory = SubCategory::firstOrCreate([
                'subCategoryname' => $row['sub_category_name'],
                'main_category_id' => $mainCategory->id,
            ], ['location_id' => $authId]);

            // **Store data for later use if necessary**
            $this->data[] = $row;

            // Create and return a new Product model
            $product = new Product([
                'product_name' => $row['product_name'],
                'sku' => $row['sku'], // Use generated or provided SKU
                'unit_id' => $unit->id, // Use resolved Unit ID
                'brand_id' => $brand->id, // Use resolved Brand ID
                'main_category_id' => $mainCategory->id, // Use resolved MainCategory ID
                'sub_category_id' => $subCategory->id, // Use resolved SubCategory ID
                'stock_alert' => $row['stock_alert'],
                'stock_alert_quantity' => $row['stock_alert_quantity'],
                'product_image_name' => $row['product_image_name'],
                'description' => $row['description'],
                'is_imei_or_serial_no' => $row['is_imeiserial_no'],
                'is_for_selling' => $row['is_for_selling'],
                'product_type' => $row['product_type'],
                'pax' => $row['pax'],
                'original_price' => $row['original_price'],
                'retail_price' => $row['retail_price'],
                'whole_sale_price' => $row['whole_sale_price'],
                'special_price' => $row['special_price'],
                'max_retail_price' => $row['max_retail_price'],
            ]);

            // Save the product to the database
            $product->save();

            // Get the primary key (id) of the newly created product
            $productId = $product->id;

            // Insert into location_product table
            DB::table('location_product')->insert([
                'location_id' => auth()->id(),
                'product_id' => $productId,
            ]);

            $formattedExpiryDate = $row['expiry_date']
            ? \Carbon\Carbon::parse($row['expiry_date'])->format('Y-m-d')
            : null;

            // Batch processing
            $batch = Batch::updateOrCreate(
                [
                    'batch_no' => $row['batch_no'] ?? Batch::generateNextBatchNo(),
                    'product_id' => $productId,
                ],
                [
                    'qty' => $row['qty'],
                    'unit_cost' => $row['original_price'],
                    'retail_price' => $row['retail_price'],
                    'wholesale_price' => $row['whole_sale_price'],
                    'special_price' => $row['special_price'],
                    'max_retail_price' => $row['max_retail_price'],
                    'expiry_date' => $formattedExpiryDate,
                ]
            );

            $locationBatch = LocationBatch::updateOrCreate(
                [
                    'batch_id' => $batch->id,
                    'location_id' => auth()->id(),
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

            DB::commit(); // Commit the transaction if everything is successful
        } catch (\Exception $e) {
            DB::rollBack(); // Roll back the transaction if an exception occurs
            Log::error('Transaction failed: ' . $e->getMessage()); // Log the error
            return null; // Optionally handle or return null
        }
    }

}
