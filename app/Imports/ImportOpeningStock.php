<?php

namespace App\Imports;

use App\Models\Location;
use App\Models\Product;
use App\Models\OpeningStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportOpeningStock implements ToModel, WithHeadingRow,SkipsOnFailure
{
    use SkipsFailures; // This allows skipping failed rows

    public function model(array $row)
    {
        //   dd($row);
        // Perform validation
        $validator = Validator::make($row, [
            'sku' => [
                'nullable',
                'string',
                'max:255',
                'unique:opening_stocks', // Check uniqueness in the table
                function ($attribute, $value, $fail) {
                    // Custom rule for SKU format
                    if (!preg_match('/^SKU\d{4}$/', $value)) {
                        $fail('The ' . $attribute . ' must be in the format SKU followed by 4 digits (e.g. SKU0001).');
                    }
                }
            ],
            'location' => 'required|string',
            'product' => 'required|string',
            'quantity' => 'required|numeric|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'lot_number' => 'required|numeric',
            'expiry_date' => 'required|string',
        ],
        [
            'sku.unique' => 'The SKU has already been taken.', // Custom error message for SKU uniqueness
        ]);

        // If validation fails, skip the row
        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }


        // Find the Location based on the location_name from the Excel row
        $location = Location::where('name', $row['location'])->first();
        $product = Product::where('product_name', $row['product'])->first();

        // Ensure the location is found
        if (!$location) {
            throw new \Exception('Location not found: ' . $row['location']);
        }

        if (!$product) {
            throw new \Exception('Product not found: ' . $row['product']);
        }

        return new OpeningStock([
            'sku' => $row['sku'],
            'location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => $row['quantity'],
            'unit_cost' => $row['unit_cost'],
            'lot_no' => $row['lot_number'],
            'expiry_date' => $row['expiry_date'],
        ]);
    }
}
