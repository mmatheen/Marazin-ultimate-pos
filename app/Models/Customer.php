<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory,LocationTrait;
    protected $table='customers';
    protected $fillable=[

              'prefix',
              'first_name',
              'last_name',
              'mobile_no',
              'email',
              'contact_id',
              'contact_type',
              'date',
              'assign_to',
              'opening_balance',

    ];

        // Setter for the 'date' and 'assign_to' fields to ensure they are always saved in Y-m-d format
        public function setDateAttribute($value)
        {
            // Ensure 'date' is saved in the 'Y-m-d' format
            $this->attributes['date'] = Carbon::parse($value)->format('Y-m-d');
        }

        // Optional: Getter for the 'date' field if you want to always return it in a specific format
        public function getDateAttribute($value)
        {
            // Ensure the 'date' is returned in 'Y-m-d' format
            return Carbon::parse($value)->format('Y-m-d');
        }

        // Add this method in your Customer model
        public function getFullNameAttribute()
        {
            return $this->first_name . ' ' . $this->last_name;
        }

}
