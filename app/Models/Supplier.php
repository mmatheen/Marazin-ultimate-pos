<?php
namespace App\Models;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory,LocationTrait;
    protected $table = 'suppliers';
    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_no',
        'email',
        'address',
        'opening_balance',
        'current_balance',

    ];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Calculate current balance for the supplier.
     */
    public function getCurrentBalanceAttribute()
    {
        $openingBalance = $this->opening_balance ?? 0;
        $totalPurchases = $this->purchases()->sum('final_total') ?? 0;
        $totalPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount') ?? 0;
        $totalReturns = $this->purchaseReturns()->sum('return_total') ?? 0;
        
        return $openingBalance + $totalPurchases - $totalPayments - $totalReturns;
    }

    /**
     * Calculate total due for the supplier.
     */
    public function getTotalDue()
    {
        $totalPurchases = \App\Models\Purchase::where('supplier_id', $this->id)->sum('final_total');
        $totalPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount');
        return $totalPurchases - $totalPayments;
    }

    /**
     * Calculate total paid for the supplier.
     */
    public function getTotalPaid()
    {
        return \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount');
    }

    public function purchases()
    {
        return $this->hasMany(\App\Models\Purchase::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(\App\Models\PurchaseReturn::class);
    }

    // Total Purchase Due for the supplier
    public function getTotalPurchaseDueAttribute()
    {
        $totalPurchases = $this->purchases()->sum('final_total') ?? 0;
        $totalPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount') ?? 0;
        return $totalPurchases - $totalPayments;
    }

    // Total Return Due for the supplier
    public function getTotalReturnDueAttribute()
    {
        $totalReturns = $this->purchaseReturns()->sum('return_total') ?? 0;
        $totalReturnPayments = \App\Models\Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase_return')->sum('amount') ?? 0;
        return $totalReturns - $totalReturnPayments;
    }
}
