<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\SpWorkOrder::class => \App\Policies\SpWorkOrderPolicy::class,
        \App\Models\FirstPieceInspection::class => \App\Policies\FirstPieceInspectionPolicy::class,
        \App\Models\IpqcInspection::class => \App\Policies\IpqcInspectionPolicy::class,
        \App\Models\SecondProcessReport::class => \App\Policies\SecondProcessReportPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('SUPER-ADMIN')) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }
        });

        Gate::define('manage-users-roles', function ($user) {
            return $user->hasPermission('manage-users-roles') || $user->hasRoleAccess('SUPER-ADMIN');
        });

        Gate::define('manage-branches-departments', function ($user) {
            return $user->hasPermission('manage-branches-departments') || $user->hasRoleAccess('SUPER-ADMIN');
        });

        Gate::define('view-admin-links', function ($user) {
            return $user->hasPermission('view-admin-links');
        });

        Gate::define('view-workshop-links', function ($user) {
            return $user->hasPermission('view-workshop-links');
        });

        Gate::define('view-warehouse-links', function ($user) {
            return $user->hasPermission('view-warehouse-links');
        });

        Gate::define('view-operator-links', function ($user) {
            return $user->hasPermission('view-operator-links');
        });

        Gate::define('view-pe-links', function ($user) {
            return $user->hasPermission('view-pe-links');
        });

        Gate::define('view-store-links', function ($user) {
            return $user->hasPermission('view-store-links');
        });

        Gate::define('view-ppic-links', function ($user) {
            return $user->hasPermission('view-ppic-links');
        });

        Gate::define('view-maintenance-links', function ($user) {
            return $user->hasPermission('view-maintenance-links');
        });

        // Second Process Feature Gates
        Gate::define('second-process-work-orders', fn ($user) => $user->hasPermission('second-process-work-orders'));
        Gate::define('second-process-first-piece', fn ($user) => $user->hasPermission('second-process-first-piece'));
        Gate::define('second-process-ipqc', fn ($user) => $user->hasPermission('second-process-ipqc'));
        Gate::define('second-process-dashboard', fn ($user) => $user->hasPermission('second-process-dashboard'));
        Gate::define('second-process-sessions', fn ($user) => $user->hasPermission('second-process-sessions'));
        Gate::define('second-process-approvals', fn ($user) => $user->hasPermission('second-process-approvals'));
        Gate::define('second-process-reports', fn ($user) => $user->hasPermission('second-process-reports'));
        Gate::define('second-process-analytics', fn ($user) => $user->hasPermission('second-process-analytics'));

        // Legacy compatibility gates for Second Process
        Gate::define('view-second-process-links', fn ($user) => Gate::forUser($user)->allows('second-process-work-orders'));
        Gate::define('view-second-process-ops', fn ($user) => Gate::forUser($user)->allows('second-process-dashboard') || Gate::forUser($user)->allows('second-process-sessions'));
        Gate::define('approve-sp-sessions', fn ($user) => Gate::forUser($user)->allows('second-process-approvals'));

        Gate::define('execute-qc-inspections', function ($user) {
            return $user->hasPermission('execute-qc-inspections');
        });

        Gate::define('view-assembly-process-links', function ($user) {
            return $user->hasPermission('view-assembly-process-links');
        });

        Gate::define('view-business-links', function ($user) {
            return $user->hasPermission('view-business-links');
        });

        Gate::define('view-production-links', function ($user) {
            return $user->hasPermission('view-production-links');
        });

        Gate::define('view-quality-links', function ($user) {
            return $user->hasPermission('view-quality-links');
        });
    }
}
