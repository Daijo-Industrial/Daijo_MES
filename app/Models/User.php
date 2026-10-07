<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'zone_id',
        'branch_id',
        'department_id',
        'api_token',
        'role_id',
        'is_active',
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
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function dailyItemCode()
    {
        return $this->hasMany(DailyItemCode::class);
    }

    public function jobs()
    {
        return $this->hasOne(MachineJob::class, 'user_id');
    }

    public function specification()
    {
        return $this->belongsTo(Specification::class);
    }

    public function hasRole($role)
    {
        return $this->role && strcasecmp($this->role->name, $role) === 0;
    }

    public function hasRoleAccess($requiredRole)
    {
        if (! $this->role) {
            return false;
        }

        if ($this->hasRole('SUPER-ADMIN')) {
            return true;
        }

        // Get the role hierarchy from config
        $roleHierarchy = config('roles.hierarchy');

        // Get the current user's role
        $userRole = $this->role->name;

        if (! isset($roleHierarchy[$userRole])) {
            return false;
        }

        // Check if the user's role is allowed to access the required role
        return in_array($requiredRole, $roleHierarchy[$userRole]);
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->hasRole('SUPER-ADMIN')) {
            return true;
        }

        if (! $this->role) {
            return false;
        }

        return $this->role->hasPermission($permissionName);
    }

    /**
     * Determine if user is authorized to sign for a specific signature role/slot in a given domain.
     */
    public function canSign(string $domain, string $role): bool
    {
        if ($this->hasRole('SUPER-ADMIN') || $this->hasRole('ADMIN')) {
            return true;
        }

        if (! $this->role) {
            return false;
        }

        $userRole = strtoupper(trim($this->role->name));
        $allowed = config("roles.signature_mapping.{$domain}.{$role}", []);

        $normalized = array_map(fn ($r) => strtoupper(trim($r)), $allowed);

        if (in_array($userRole, $normalized)) {
            return true;
        }

        // Check role hierarchy from config/roles.php
        $roleHierarchy = config('roles.hierarchy');
        if (isset($roleHierarchy[$userRole])) {
            foreach ($roleHierarchy[$userRole] as $inheritedRole) {
                if (in_array(strtoupper(trim($inheritedRole)), $normalized)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function zone()
    {
        return $this->belongsTo(MasterZone::class, 'zone_id');
    }

    public function zoneLogs()
    {
        return $this->hasMany(ZoneLog::class, 'zone_id', 'zone_id');
    }
}
