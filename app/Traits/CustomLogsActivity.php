<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Log;

trait CustomLogsActivity
{
    // Set the $customLogName property in your Model to customize the log name

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->useLogName($this->getCustomLogName())
            ->setDescriptionForEvent(function (string $eventName) {
                try {
                    // Get current authenticated user
                    $currentUser = auth()->user();
                    $userName = $currentUser ? $currentUser->user_name : 'Unknown';
                    
                    // Generate detailed description based on model type
                    $description = $this->generateDetailedDescription($eventName, $userName);
                    
                    // Log for debugging (can be removed in production)
                    Log::info('Activity Log Description Generated', [
                        'model' => get_class($this),
                        'event' => $eventName,
                        'description' => $description
                    ]);
                    
                    return $description;
                } catch (\Exception $e) {
                    // Fallback to simple description if anything fails
                    Log::error('Activity Log Description Error', [
                        'error' => $e->getMessage(),
                        'model' => get_class($this),
                        'event' => $eventName
                    ]);
                    
                    $userName = auth()->user()->user_name ?? 'Unknown';
                    return "A {$this->getCustomLogName()} has been {$eventName} by user: {$userName}";
                }
            });
    }

    protected function generateDetailedDescription(string $eventName, string $userName): string
    {
        $modelName = $this->getCustomLogName();
        
        // Handle different model types with specific details
        switch ($modelName) {
            case 'sale':
                return $this->generateSaleDescription($eventName, $userName);
            case 'purchase':
                return $this->generatePurchaseDescription($eventName, $userName);
            case 'product':
                return $this->generateProductDescription($eventName, $userName);
            default:
                // Fallback to generic description
                return "A {$modelName} has been {$eventName} by user: {$userName}";
        }
    }

    protected function generateSaleDescription(string $eventName, string $userName): string
    {
        $description = "";
        
        if ($eventName === 'created') {
            $invoiceNo = $this->invoice_no ?? $this->id ?? 'N/A';
            $description = "Sale #{$invoiceNo} has been created by user: {$userName}";
        } elseif ($eventName === 'updated') {
            $customerName = 'Walk-In Customer';
            $totalAmount = number_format($this->final_total ?? 0, 2);
            $invoiceNo = $this->invoice_no ?? $this->id ?? 'N/A';
            
            // Try to get customer name safely
            try {
                if (isset($this->customer_id) && $this->customer_id != 1) {
                    // Try to load customer relationship if not already loaded
                    if (!$this->relationLoaded('customer')) {
                        $this->load('customer');
                    }
                    
                    if ($this->customer && isset($this->customer->full_name)) {
                        $customerName = $this->customer->full_name;
                    } else {
                        // Fallback: try to fetch customer directly from database
                        $customer = \App\Models\Customer::find($this->customer_id);
                        if ($customer) {
                            $customerName = $customer->full_name ?? 'Unknown Customer';
                        }
                    }
                }
            } catch (\Exception $e) {
                // If anything fails, just use the default customer name
                $customerName = 'Walk-In Customer';
            }
            
            $description = "Sale #{$invoiceNo} (Customer: {$customerName}, Amount: Rs.{$totalAmount}) has been updated by user: {$userName}";
            
            // Add details about what changed if available
            try {
                if ($this->isDirty()) {
                    $changedFields = array_keys($this->getDirty());
                    $fieldList = implode(', ', $changedFields);
                    $description .= " - Changed fields: {$fieldList}";
                }
            } catch (\Exception $e) {
                // If getting dirty fields fails, continue without change details
            }
        } elseif ($eventName === 'deleted') {
            $invoiceNo = $this->invoice_no ?? $this->id ?? 'N/A';
            $description = "Sale #{$invoiceNo} has been deleted by user: {$userName}";
        } else {
            $invoiceNo = $this->invoice_no ?? $this->id ?? 'N/A';
            $description = "Sale #{$invoiceNo} has been {$eventName} by user: {$userName}";
        }
        
        return $description;
    }

    protected function generatePurchaseDescription(string $eventName, string $userName): string
    {
        if ($eventName === 'created') {
            // Load supplier relationship if not already loaded
            if (!$this->relationLoaded('supplier')) {
                $this->load('supplier');
            }
            
            $supplierName = 'Unknown Supplier';
            if ($this->supplier) {
                $supplierName = $this->supplier->full_name ?? 'Unknown Supplier';
            }
            
            $referenceNo = $this->reference_no ?? $this->id ?? 'N/A';
            $totalAmount = number_format($this->final_total ?? 0, 2);
            return "Purchase #{$referenceNo} (Supplier: {$supplierName}, Amount: Rs.{$totalAmount}) has been created by user: {$userName}";
        } elseif ($eventName === 'updated') {
            // Load supplier relationship if not already loaded
            if (!$this->relationLoaded('supplier')) {
                $this->load('supplier');
            }
            
            $supplierName = 'Unknown Supplier';
            if ($this->supplier) {
                $supplierName = $this->supplier->full_name ?? 'Unknown Supplier';
            }
            
            $referenceNo = $this->reference_no ?? $this->id ?? 'N/A';
            $totalAmount = number_format($this->final_total ?? 0, 2);
            $description = "Purchase #{$referenceNo} (Supplier: {$supplierName}, Amount: Rs.{$totalAmount}) has been updated by user: {$userName}";
            
            // Add details about what changed if available
            if ($this->isDirty()) {
                $changedFields = array_keys($this->getDirty());
                $fieldList = implode(', ', $changedFields);
                $description .= " - Changed fields: {$fieldList}";
            }
            
            return $description;
        } elseif ($eventName === 'deleted') {
            $referenceNo = $this->reference_no ?? $this->id ?? 'N/A';
            return "Purchase #{$referenceNo} has been deleted by user: {$userName}";
        }
        
        return "A purchase has been {$eventName} by user: {$userName}";
    }

    protected function generateProductDescription(string $eventName, string $userName): string
    {
        $productName = $this->product_name ?? 'Unknown Product';
        
        if ($eventName === 'updated') {
            $sku = $this->sku ?? 'N/A';
            return "Product '{$productName}' (SKU: {$sku}) has been updated by user: {$userName}";
        }
        
        return "Product '{$productName}' has been {$eventName} by user: {$userName}";
    }

    protected function getCustomLogName(): string
    {
        // Use the property if it exists, otherwise fallback to 'default'
        return property_exists($this, 'customLogName') ? $this->customLogName : 'default';
    }
}
