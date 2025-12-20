<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\City;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class CustomerImport implements ToCollection, WithHeadingRow
{
    private $data = [];
    private $validationErrors = [];
    private $currentRow = 1;
    private $locationId;
    private $overrideCityId; // City ID to apply to all customers

    public function getData()
    {
        return $this->data;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    public function __construct($locationId = null, $overrideCityId = null)
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        $this->currentRow = 1;
        $this->locationId = $locationId ?? 1; // Default to location ID 1
        $this->overrideCityId = $overrideCityId; // If set, all customers will use this city_id
    }

    public function collection(Collection $rows)
    {
        // First pass: Validate all rows without inserting anything
        $this->validateAllRows($rows);

        // If there are any validation errors, don't proceed with import
        if (!empty($this->validationErrors)) {
            Log::error("Import cancelled due to validation errors:", $this->validationErrors);
            return;
        }

        // Second pass: Import all rows (only if validation passed)
        DB::beginTransaction();

        try {
            $processedCount = 0;
            foreach ($rows as $index => $row) {
                $excelRowNumber = $index + 2;

                try {
                    $result = $this->processRow($row->toArray(), $excelRowNumber);
                    if ($result !== null) {
                        $processedCount++;
                    }
                } catch (\Exception $rowException) {
                    Log::error("Error processing row {$excelRowNumber}: " . $rowException->getMessage());
                    $this->validationErrors[] = "Row {$excelRowNumber}: " . $rowException->getMessage();
                    continue;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->validationErrors[] = "Import failed: " . $e->getMessage();
            Log::error("Import transaction failed: " . $e->getMessage());
            Log::error("Exception details: " . $e->getTraceAsString());
        }
    }

    private function validateAllRows(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $excelRowNumber = $index + 2;
            $this->validateRow($row->toArray(), $excelRowNumber);
        }
    }

    private function validateRow(array $row, int $rowNumber)
    {
        // Convert mobile_no to string for validation (Excel may import as number)
        if (isset($row['mobile_no'])) {
            $row['mobile_no'] = strval($row['mobile_no']);
        }

        // Convert email to string if present
        if (isset($row['email']) && !empty($row['email'])) {
            $row['email'] = strval($row['email']);
        }

        // Normalize customer_type to lowercase for validation
        if (isset($row['customer_type']) && !empty($row['customer_type'])) {
            $row['customer_type'] = strtolower(trim($row['customer_type']));
        }

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'mobile_no' => 'required|string|max:20|unique:customers,mobile_no',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric',
            'credit_limit' => 'nullable|numeric|min:0',
            'city_name' => 'nullable|string|max:255',
            'customer_type' => 'nullable|in:wholesaler,retailer',
        ];

        $messages = [
            'first_name.required' => 'First name is required',
            'mobile_no.required' => 'Mobile number is required',
            'mobile_no.unique' => 'Mobile number already exists',
            'email.email' => 'Invalid email format',
            'email.unique' => 'Email already exists',
            'customer_type.in' => 'Customer type must be wholesaler or retailer',
        ];

        $validator = Validator::make($row, $rules, $messages);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->validationErrors[] = "Row {$rowNumber}: {$error}";
            }
        }

        // Validate city exists
        if (!empty($row['city_name'])) {
            $city = City::where('name', $row['city_name'])->first();
            if (!$city) {
                // Don't add validation error - we'll create the city during import
                // $this->validationErrors[] = "Row {$rowNumber}: City '{$row['city_name']}' not found";
            }
        }
    }

    private function processRow(array $row, int $rowNumber)
    {
        try {
            // Check if we have an override city_id from the form
            if ($this->overrideCityId) {
                // Use the selected city from dropdown (ignore Excel city name)
                $cityId = $this->overrideCityId;
                Log::info("Using override city ID: {$cityId} for customer import");
            } else {
                // Use city from Excel or create new one
                $cityId = null;
                if (!empty($row['city_name'])) {
                    // Try to find existing city or create new one with required fields
                    $city = City::firstOrCreate(
                        ['name' => trim($row['city_name'])],
                        [
                            'name' => trim($row['city_name']),
                            'district' => 'ampara', // Default district
                            'province' => 'Eastern' // Default province
                        ]
                    );
                    $cityId = $city->id;
                    Log::info("City processed for customer import: {$city->name} (ID: {$cityId})");
                }
                // If no city in Excel and no override, cityId remains null
            }

            // Convert mobile number to string if it's numeric
            $mobileNo = isset($row['mobile_no']) ? strval($row['mobile_no']) : '';

            // Convert email to string to avoid issues
            $email = isset($row['email']) && !empty($row['email']) ? strval($row['email']) : null;

            // Normalize customer_type to lowercase and trim whitespace
            $customerType = 'retailer'; // Default value
            if (!empty($row['customer_type'])) {
                $normalizedType = strtolower(trim($row['customer_type']));
                if (in_array($normalizedType, ['wholesaler', 'retailer'])) {
                    $customerType = $normalizedType;
                }
            }

            $customerData = [
                'prefix' => $row['prefix'] ?? null,
                'first_name' => trim($row['first_name']),
                'last_name' => !empty($row['last_name']) ? trim($row['last_name']) : null,
                'mobile_no' => trim($mobileNo),
                'email' => $email ? trim($email) : null,
                'address' => !empty($row['address']) ? trim($row['address']) : null,
                'opening_balance' => !empty($row['opening_balance']) ? (float)$row['opening_balance'] : 0,
                'credit_limit' => !empty($row['credit_limit']) ? (float)$row['credit_limit'] : null,
                'city_id' => $cityId, // Can be null, from dropdown, or from Excel
                'customer_type' => $customerType,
                'location_id' => $this->locationId,
            ];

            $customer = Customer::create($customerData);

            // Explicitly sync opening balance to ledger if customer has opening balance
            // The model boot event should handle this, but we ensure it here for imports
            if ($customer->opening_balance != 0 && $customer->id != 1) {
                $customer->syncOpeningBalanceToLedger();
                Log::info("Synced opening balance to ledger for customer ID: {$customer->id}, Amount: {$customer->opening_balance}");
            }

            $this->data[] = [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'mobile_no' => $customer->mobile_no,
                'opening_balance' => $customer->opening_balance,
                'status' => 'success'
            ];

            return $customer;

        } catch (\Exception $e) {
            Log::error("Error importing customer at row {$rowNumber}: " . $e->getMessage());
            throw $e;
        }
    }
}
