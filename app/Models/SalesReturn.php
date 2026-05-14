<?php

namespace App\Models;

use App\Services\Shared\ReturnPaymentStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\CustomLogsActivity;

class SalesReturn extends Model
{
    use HasFactory, LogsActivity, CustomLogsActivity;

     protected string $customLogName = 'sale_return';


    protected $fillable = [
        'sale_id',
        'customer_id',
        'location_id',
        'return_date',
        'return_total',
        'discount_type',
        'discount_amount',
        'total_paid',
        'total_due',
        'payment_status',
        'notes',
        'is_defective',
        'invoice_number',
        'stock_type',
        'user_id',
    ];


    /**
     * Relationship with Sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship with Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with Location.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Relationship with SalesReturnProduct.
     */
    public function returnProducts()
    {
        return $this->hasMany(SalesReturnProduct::class, 'sales_return_id');
    }

    /**
     * Relationship with Payment.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id', 'id')
            ->where(function ($query) {
                $query->where('payment_type', 'sale_return_with_bill')
                    ->orWhere('payment_type', 'sale_return_without_bill');
            });
    }

    /**
     * Boot method to generate invoice number on creating.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->invoice_number = self::generateInvoiceNumber();
        });

        // Calculate total_due and payment_status
        static::saving(function ($model) {
            // Only auto-calculate if not manually set
            if (!$model->isDirty('total_paid')) {
                $model->total_paid = $model->payments()->sum('amount');
            }

            $model->payment_status = app(ReturnPaymentStatusService::class)
                ->derive((float) $model->return_total, (float) $model->total_paid);

            // Plain total_due column (no DB GENERATED): must be set or MySQL strict mode errors (1364).
            // Skip when the column is generated — MySQL rejects explicit values on insert/update.
            if (! static::totalDueIsDatabaseGenerated()) {
                $model->total_due = round(
                    (float) $model->return_total - (float) $model->total_paid,
                    2
                );
            }
        });
    }

    /**
     * Whether total_due is a MySQL/MariaDB generated (stored/virtual) column.
     */
    public static function totalDueIsDatabaseGenerated(): bool
    {
        $connection = (new static)->getConnection();
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        $db = $connection->getDatabaseName();
        $table = $connection->getTablePrefix().(new static)->getTable();
        $row = $connection->selectOne(
            "SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'total_due'",
            [$db, $table]
        );

        return $row && str_contains(strtolower((string) ($row->EXTRA ?? '')), 'generated');
    }

    /**
     * Generate invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        // lockForUpdate prevents duplicate invoice numbers under concurrent requests
        $latest = self::lockForUpdate()->latest('id')->first();
        $number = $latest ? intval(substr($latest->invoice_number, -4)) + 1 : 1;
        return 'SR-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update the total due amount.
     */
    public function updateTotalDue()
    {
        $this->total_paid = $this->payments()->sum('amount');
        $this->save();
        $this->refresh();

        return $this;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
