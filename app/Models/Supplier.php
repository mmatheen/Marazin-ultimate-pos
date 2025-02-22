<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;
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
        'location_id',
    ];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Calculate total due for the supplier.
     */
    public function getTotalDue()
    {
        $totalPurchases = Purchase::where('supplier_id', $this->id)->sum('final_total');
        $totalPayments = Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount');
        return $totalPurchases - $totalPayments;
    }

    /**
     * Calculate total paid for the supplier.
     */
    public function getTotalPaid()
    {
        return Payment::where('supplier_id', $this->id)->where('payment_type', 'purchase')->sum('amount');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    // Total Purchase Due for the supplier
    public function getTotalPurchaseDueAttribute()
    {
        return $this->purchases()->sum('total_due');
    }

    // Total Return Due for the supplier
    public function getTotalReturnDueAttribute()
    {
        return $this->purchaseReturns()->sum('total_due');
    }
}
