<?php

namespace App\Helpers;

use App\Models\Customer;

class CustomerHelper
{
    /**
     * Get the walk-in customer ID dynamically
     * Priority: ID 1 first, then search by mobile numbers
     */
    public static function getWalkInCustomerId()
    {
        static $walkInCustomerId = null;
        
        if ($walkInCustomerId === null) {
            // First, check if customer ID 1 exists and looks like a walk-in customer
            $customerOne = Customer::find(1);
            if ($customerOne && 
                (stripos($customerOne->first_name, 'walk') !== false || 
                 $customerOne->mobile_no === '0000000000' || 
                 $customerOne->mobile_no === '0111111111')) {
                $walkInCustomerId = 1;
            } else {
                // Fallback: search by known walk-in mobile numbers
                $walkInCustomer = Customer::whereIn('mobile_no', ['0111111111', '0000000000'])
                    ->orWhere('first_name', 'LIKE', '%walk%')
                    ->orderBy('id', 'asc') // Prefer lower ID
                    ->first();
                
                $walkInCustomerId = $walkInCustomer ? $walkInCustomer->id : 1; // fallback to 1
            }
        }
        
        return $walkInCustomerId;
    }
    
    /**
     * Check if a customer ID is the walk-in customer
     */
    public static function isWalkInCustomer($customerId)
    {
        return $customerId == self::getWalkInCustomerId();
    }
    
    /**
     * Get walk-in customer object
     */
    public static function getWalkInCustomer()
    {
        return Customer::find(self::getWalkInCustomerId());
    }
    
    /**
     * Get all potential walk-in customers (for cleanup purposes)
     */
    public static function getAllWalkInCustomers()
    {
        return Customer::where(function($query) {
            $query->whereIn('mobile_no', ['0111111111', '0000000000'])
                  ->orWhere('first_name', 'LIKE', '%walk%')
                  ->orWhere('email', 'LIKE', '%walking%');
        })->get();
    }
}