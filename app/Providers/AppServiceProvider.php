<?php

namespace App\Providers;

use App\Domain\Inventory\Contracts\FgInventoryRepositoryInterface;
use App\Domain\Inventory\Contracts\RejectRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\EloquentFgInventoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentRejectRepository;
use App\Models\SecondProcessReport;
// DDD — Inventory feature bindings
use App\Policies\SecondProcessReportPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Yajra\DataTables\Html\Builder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Inventory — Stock Health Dashboard
        $this->app->bind(FgInventoryRepositoryInterface::class, EloquentFgInventoryRepository::class);
        $this->app->bind(RejectRepositoryInterface::class, EloquentRejectRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Builder::useVite();

        Gate::policy(SecondProcessReport::class, SecondProcessReportPolicy::class);

        RateLimiter::for('sap-api', function (Request $request) {

            return Limit::perMinute(3000) // 30 request / menit
                ->by(
                    optional($request->user())->id
                    ?: $request->header('X-Device-ID')
                    ?: $request->ip()
                );
        });
    }
}
