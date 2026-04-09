<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;

class CustomerCrudService
{
    public function findByIdWithCity(int $id): ?Customer
    {
        return Customer::withoutLocationScope()->with(['city'])->find($id);
    }

    public function createFromInput(array $input, bool $includeAllowSms = false): array
    {
        $duplicateErrors = $this->getDuplicateErrorsForCreate(
            (string) ($input['mobile_no'] ?? ''),
            isset($input['email']) ? (string) $input['email'] : null
        );

        if ($duplicateErrors !== null) {
            return ['errors' => $duplicateErrors];
        }

        DB::beginTransaction();

        try {
            $customerData = $this->buildCustomerData($input, null, $includeAllowSms);
            $customer = Customer::create($customerData);

            DB::commit();

            return [
                'customer' => $customer,
                'calculated_credit_limit' => $customerData['credit_limit'] ?? 0,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateFromInput(
        Customer $customer,
        array $input,
        bool $includeAllowSms = false,
        bool $recordOpeningBalanceAdjustment = false,
        ?UnifiedLedgerService $unifiedLedgerService = null,
        string $openingBalanceNote = 'Opening balance updated via API'
    ): array {
        $duplicateErrors = $this->getDuplicateErrorsForUpdate(
            $customer->id,
            (string) ($input['mobile_no'] ?? ''),
            isset($input['email']) ? (string) $input['email'] : null
        );

        if ($duplicateErrors !== null) {
            return ['errors' => $duplicateErrors];
        }

        DB::beginTransaction();

        try {
            $oldOpeningBalance = (float) $customer->opening_balance;
            $newOpeningBalance = array_key_exists('opening_balance', $input)
                ? (float) $input['opening_balance']
                : $oldOpeningBalance;

            $customerData = $this->buildCustomerData($input, $customer, $includeAllowSms);
            $customer->update($customerData);

            if ($recordOpeningBalanceAdjustment && $unifiedLedgerService && $oldOpeningBalance != $newOpeningBalance) {
                $unifiedLedgerService->recordOpeningBalanceAdjustment(
                    $customer->id,
                    'customer',
                    $oldOpeningBalance,
                    $newOpeningBalance,
                    $openingBalanceNote
                );
            }

            DB::commit();

            return [
                'customer' => $customer,
                'calculated_credit_limit' => $customerData['credit_limit'] ?? $customer->credit_limit,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getDuplicateFieldFromException(\Throwable $e): ?string
    {
        $errorMessage = $e->getMessage();

        if (
            strpos($errorMessage, 'mobile') !== false ||
            strpos($errorMessage, 'mobile_no') !== false ||
            strpos($errorMessage, 'customers_mobile_no_unique') !== false
        ) {
            return 'mobile_no';
        }

        if (strpos($errorMessage, 'email') !== false || strpos($errorMessage, 'customers_email_unique') !== false) {
            return 'email';
        }

        return null;
    }

    private function getDuplicateErrorsForCreate(string $mobileNo, ?string $email): ?array
    {
        if ($mobileNo !== '') {
            $existingCustomer = Customer::withoutLocationScope()->where('mobile_no', $mobileNo)->first();
            if ($existingCustomer) {
                return [
                    'mobile_no' => ['This mobile number is already registered with another customer.'],
                ];
            }
        }

        if (!empty($email)) {
            $existingCustomerByEmail = Customer::withoutLocationScope()->where('email', $email)->first();
            if ($existingCustomerByEmail) {
                return [
                    'email' => ['This email address is already registered with another customer.'],
                ];
            }
        }

        return null;
    }

    private function getDuplicateErrorsForUpdate(int $id, string $mobileNo, ?string $email): ?array
    {
        if ($mobileNo !== '') {
            $existingCustomer = Customer::withoutLocationScope()
                ->where('mobile_no', $mobileNo)
                ->where('id', '!=', $id)
                ->first();

            if ($existingCustomer) {
                return [
                    'mobile_no' => ['This mobile number is already registered with another customer.'],
                ];
            }
        }

        if (!empty($email)) {
            $existingCustomerByEmail = Customer::withoutLocationScope()
                ->where('email', $email)
                ->where('id', '!=', $id)
                ->first();

            if ($existingCustomerByEmail) {
                return [
                    'email' => ['This email address is already registered with another customer.'],
                ];
            }
        }

        return null;
    }

    private function buildCustomerData(array $input, ?Customer $existingCustomer = null, bool $includeAllowSms = false): array
    {
        $customerData = [
            'prefix' => $input['prefix'] ?? null,
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'mobile_no' => $input['mobile_no'] ?? null,
            'email' => $input['email'] ?? null,
            'address' => $input['address'] ?? null,
            'opening_balance' => $input['opening_balance'] ?? null,
            'credit_limit' => $input['credit_limit'] ?? null,
            'city_id' => $input['city_id'] ?? null,
            'customer_type' => $input['customer_type'] ?? null,
        ];

        if ($includeAllowSms) {
            $customerData['allow_sms'] = filter_var($input['allow_sms'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $hasCreditLimit = array_key_exists('credit_limit', $input) && $input['credit_limit'] !== null && $input['credit_limit'] !== '';
        $newCityId = $input['city_id'] ?? null;

        if (! $hasCreditLimit) {
            if ($existingCustomer === null && $newCityId) {
                $customerData['credit_limit'] = Customer::calculateCreditLimitForCity($newCityId);
            }

            if ($existingCustomer !== null && $newCityId != $existingCustomer->city_id) {
                $oldCalculatedLimit = Customer::calculateCreditLimitForCity($existingCustomer->city_id);
                if ($existingCustomer->credit_limit == $oldCalculatedLimit) {
                    $customerData['credit_limit'] = Customer::calculateCreditLimitForCity($newCityId);
                }
            }
        }

        return $customerData;
    }
}
