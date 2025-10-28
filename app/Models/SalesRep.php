<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalesRep extends Model
{
    use HasFactory;

    protected $table = 'sales_reps';

    // Assignment Status Constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_UPCOMING => 'Upcoming',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'user_id',
        'sub_location_id',
        'route_id',
        'assigned_date',
        'end_date',
        'can_sell',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'datetime',
        'end_date' => 'datetime',
        'can_sell' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subLocation()
    {
        return $this->belongsTo(Location::class, 'sub_location_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    // Scopes for different assignment statuses
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_UPCOMING);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // Scope for assignments that should be active based on dates
    public function scopeActiveByDate($query)
    {
        $today = Carbon::today();
        return $query->where('assigned_date', '<=', $today)
                    ->where(function($q) use ($today) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $today);
                    });
    }

    // Scope for assignments that should be expired based on dates
    public function scopeExpiredByDate($query)
    {
        $today = Carbon::today();
        return $query->whereNotNull('end_date')
                    ->where('end_date', '<', $today);
    }

    // Scope for assignments that should be upcoming based on dates
    public function scopeUpcomingByDate($query)
    {
        $today = Carbon::today();
        return $query->where('assigned_date', '>', $today);
    }

    // Check if assignment is currently active based on dates
    public function isActiveByDate(): bool
    {
        $today = Carbon::today();
        $assignedDate = Carbon::parse($this->assigned_date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;

        return $assignedDate->lte($today) && 
               ($endDate === null || $endDate->gte($today));
    }

    // Check if assignment is expired based on dates
    public function isExpiredByDate(): bool
    {
        if (!$this->end_date) {
            return false; // No end date means ongoing
        }

        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        return $endDate->lt($today);
    }

    // Check if assignment is upcoming based on dates
    public function isUpcomingByDate(): bool
    {
        $today = Carbon::today();
        $assignedDate = Carbon::parse($this->assigned_date);

        return $assignedDate->gt($today);
    }

    // Get the calculated status based on dates
    public function getCalculatedStatus(): string
    {
        if ($this->isUpcomingByDate()) {
            return self::STATUS_UPCOMING;
        } elseif ($this->isExpiredByDate()) {
            return self::STATUS_EXPIRED;
        } else {
            return self::STATUS_ACTIVE;
        }
    }

    // Update status based on current dates
    public function updateStatusByDate(): bool
    {
        $calculatedStatus = $this->getCalculatedStatus();
        
        if ($this->status !== $calculatedStatus && $this->status !== self::STATUS_CANCELLED) {
            $this->status = $calculatedStatus;
            return $this->save();
        }

        return false; // No update needed
    }

    // Get status badge color for UI
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'badge-success',
            self::STATUS_EXPIRED => 'badge-danger',
            self::STATUS_UPCOMING => 'badge-info',
            self::STATUS_CANCELLED => 'badge-secondary',
            default => 'badge-light',
        };
    }

    // Get days until expiry (null if no end date, negative if expired)
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        return $today->diffInDays($endDate, false);
    }

    // Check if assignment is expiring soon (within specified days)
    public function isExpiringSoon(int $days = 3): bool
    {
        $daysUntilExpiry = $this->getDaysUntilExpiry();
        
        return $daysUntilExpiry !== null && 
               $daysUntilExpiry <= $days && 
               $daysUntilExpiry >= 0;
    }
}
