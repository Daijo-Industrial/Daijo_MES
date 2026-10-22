<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'permissions_configured',
    ];

    protected $casts = [
        'permissions_configured' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    public function isProtected(): bool
    {
        return in_array(strtoupper(trim($this->name)), ['SUPER-ADMIN', 'ADMIN']);
    }

    public function hasPermission(string $permissionName): bool
    {
        if (strtoupper(trim($this->name)) === 'SUPER-ADMIN') {
            return true;
        }

        if ($this->permissions_configured) {
            if (! $this->relationLoaded('permissions')) {
                $this->load('permissions');
            }

            return $this->permissions->contains('name', $permissionName);
        }

        return $this->defaultPermissionFallback($permissionName);
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
        $this->update(['permissions_configured' => true]);
    }

    public function givePermission(Permission|string $permission): void
    {
        $perm = is_string($permission)
            ? Permission::firstOrCreate(['name' => $permission], ['label' => $permission, 'group' => 'General'])
            : $permission;

        $this->permissions()->syncWithoutDetaching([$perm->id]);
        $this->update(['permissions_configured' => true]);
    }

    public function revokePermission(Permission|string $permission): void
    {
        $perm = is_string($permission)
            ? Permission::where('name', $permission)->first()
            : $permission;

        if ($perm) {
            $this->permissions()->detach($perm->id);
            $this->update(['permissions_configured' => true]);
        }
    }

    public function defaultPermissionFallback(string $permissionName): bool
    {
        $roleName = strtoupper(trim($this->name));

        if ($roleName === 'SUPER-ADMIN') {
            return true;
        }

        // Canonical alias mapping for legacy permission names
        $canonicalPermission = match ($permissionName) {
            'view-second-process-work-orders', 'manage-sp-work-orders', 'view-second-process-links' => 'second-process-work-orders',
            'view-second-process-reports', 'manage-second-process-reports' => 'second-process-reports',
            'view-second-process-approvals', 'approve-sp-sessions' => 'second-process-approvals',
            'view-second-process-first-piece' => 'second-process-first-piece',
            'view-second-process-ipqc' => 'second-process-ipqc',
            'view-second-process-dashboard' => 'second-process-dashboard',
            'view-second-process-sessions' => 'second-process-sessions',
            'view-second-process-analytics' => 'second-process-analytics',
            'view-second-process-ops' => 'second-process-dashboard',
            default => $permissionName,
        };

        $defaults = [
            'ADMIN' => [
                'view-admin-links',
                'manage-branches-departments',
                'view-workshop-links',
                'view-warehouse-links',
                'view-operator-links',
                'view-pe-links',
                'view-store-links',
                'view-ppic-links',
                'view-maintenance-links',
                'second-process-work-orders',
                'second-process-first-piece',
                'second-process-ipqc',
                'second-process-dashboard',
                'second-process-sessions',
                'second-process-approvals',
                'second-process-reports',
                'second-process-analytics',
                'view-assembly-process-links',
                'view-business-links',
                'view-production-links',
                'view-quality-links',
                'execute-qc-inspections',
            ],
            'WORKSHOP' => ['view-workshop-links'],
            'WAREHOUSE' => ['view-warehouse-links'],
            'OPERATOR' => ['view-operator-links'],
            'PE' => [
                'view-pe-links',
                'second-process-work-orders',
                'second-process-dashboard',
                'second-process-reports',
                'second-process-analytics',
            ],
            'STORE' => ['view-store-links'],
            'PPIC' => [
                'view-ppic-links',
                'second-process-reports',
                'second-process-analytics',
            ],
            'MAINTENANCE' => ['view-maintenance-links'],
            'SECONDPROCESS' => [
                'second-process-work-orders',
                'second-process-first-piece',
                'second-process-ipqc',
                'second-process-dashboard',
                'second-process-sessions',
                'second-process-approvals',
                'second-process-reports',
                'second-process-analytics',
            ],
            'ASSEMBLYPROCESS' => ['view-assembly-process-links'],
            'BUSINESS' => ['view-business-links'],
            'PRODUCTION' => ['view-production-links'],
            'QUALITY' => [
                'view-quality-links',
                'execute-qc-inspections',
                'second-process-first-piece',
                'second-process-ipqc',
                'second-process-reports',
            ],
            'CHECKER' => ['view-operator-links', 'view-production-links'],
            'LEADER' => ['view-operator-links', 'view-production-links'],
            'SUPERVISOR' => ['view-operator-links', 'view-production-links'],
        ];

        return isset($defaults[$roleName]) && (
            in_array($canonicalPermission, $defaults[$roleName], true) ||
            in_array($permissionName, $defaults[$roleName], true)
        );
    }
}
