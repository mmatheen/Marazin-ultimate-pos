<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LocationTrait;
use App\Traits\RolePermissionHelper;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name_title
 * @property string $full_name
 * @property string $user_name
 * @property string $role_name
 * @property int|null $location_id
 * @property bool $is_admin
 * @property string $email
 * @property string $password
 * @method BelongsToMany locations()
 * @method BelongsTo vehicle()
 * @method HasOne salesRep()
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, RolePermissionHelper;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name_title',
        'full_name',
        'user_name',
        'role_name',
        'location_id',
        'is_admin',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user', 'user_id', 'location_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'email'])
            ->setDescriptionForEvent(fn(string $eventName) => "User has been {$eventName}");
    }

    
    public function salesRep(): HasOne
    {
        return $this->hasOne(SalesRep::class, 'user_id');
    }

    /**
     * Check if user has the Sales Rep role (via role key)
     */
    public function isSalesRep(): bool
    {
        return $this->roles()->where('key', 'sales_rep')->exists();
    }

    public function isAdmin(): bool
    {
        return $this->roles()->where('key', 'admin')->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('key', 'super_admin')->exists();
    }


    // Optional: Get the primary role key
    public function getRoleKey(): ?string
    {
        return $this->roles->first()?->key;
    }

    // Optional: Get display role name
    public function getRoleName(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Check if user has permission (includes Master Super Admin logic)
     */
    public function hasPermission($permission, $guardName = null): bool
    {
        if ($this->isMasterSuperAdmin()) {
            return true; // Master Super Admin has all permissions
        }

        return $this->hasPermissionTo($permission, $guardName);
    }

    /**
     * Check if user should bypass location scope
     */
    public function shouldBypassLocationScope(): bool
    {
        return $this->canBypassLocationScope();
    }

    /**
     * Sync the legacy role_name field with Spatie role
     */
    public function syncRoleName(): void
    {
        $role = $this->roles->first();
        if ($role) {
            $this->update(['role_name' => $role->name]);
        }
    }

    /**
     * Override the assignRole method to sync role_name
     */
    public function assignRole(...$roles)
    {
        $result = parent::assignRole(...$roles);
        $this->syncRoleName();
        return $result;
    }

    /**
     * Override the syncRoles method to sync role_name
     */
    public function syncRoles(...$roles)
    {
        $result = parent::syncRoles(...$roles);
        $this->syncRoleName();
        return $result;
    }
}
