<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobTicket extends Model
{
    protected $fillable = [
        'sale_id',
        'customer_id',
        'job_ticket_no',
        'description',
        'job_ticket_date',
        'status',
        'advance_amount',
        'balance_amount',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($jobTicket) {
            // Only assign if not already set
            if (empty($jobTicket->job_ticket_no)) {
                $latest = self::orderByDesc('id')->first();
                $number = $latest ? ((int)substr($latest->job_ticket_no, 4)) + 1 : 1;
                $jobTicket->job_ticket_no = 'JOB-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
