<?php

namespace App\Http\Middleware;

use App\Services\PlantContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecondProcessPlantAccess
{
    public function __construct(
        protected PlantContextService $plantContextService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // SUPER-ADMIN is unrestricted as-is (bypasses gating)
        if ($user->hasRole('SUPER-ADMIN') || $user->hasRoleAccess('SUPER-ADMIN')) {
            return $next($request);
        }

        // 1. Plant Context Check
        if (! $this->plantContextService->supportsSecondProcess($user)) {
            return $this->denyAccess($request, 'Akses Ditolak. Fitur Second Process tidak tersedia pada Plant ini.');
        }

        // 2. Granular Route Gate Check (Fine-Grained Second Process Domain)
        $routeName = $request->route()?->getName() ?? '';

        $isAuthorized = match (true) {
            str_starts_with($routeName, 'second-process.report-analytics') => Gate::forUser($user)->allows('second-process-analytics'),
            str_starts_with($routeName, 'second-process-reports') => Gate::forUser($user)->allows('second-process-reports'),
            str_starts_with($routeName, 'sp-work-orders') => Gate::forUser($user)->allows('second-process-work-orders'),
            str_starts_with($routeName, 'first-piece-inspections') => Gate::forUser($user)->allows('second-process-first-piece') || Gate::forUser($user)->allows('execute-qc-inspections'),
            str_starts_with($routeName, 'ipqc-inspections') => Gate::forUser($user)->allows('second-process-ipqc') || Gate::forUser($user)->allows('execute-qc-inspections'),
            str_starts_with($routeName, 'sp-approvals') => Gate::forUser($user)->allows('second-process-approvals'),
            str_starts_with($routeName, 'sp-sessions') => Gate::forUser($user)->allows('second-process-sessions') || Gate::forUser($user)->allows('second-process-dashboard'),
            $routeName === 'second-process.dashboard' || $routeName === 'second-process.line-dashboard' => Gate::forUser($user)->allows('second-process-dashboard'),
            default => Gate::forUser($user)->allows('view-second-process-ops'),
        };

        if (! $isAuthorized) {
            return $this->denyAccess($request, 'Akses Ditolak. Anda tidak memiliki izin untuk mengakses modul ini.');
        }

        return $next($request);
    }

    /**
     * Handle denied access response: 403 JSON for API/AJAX, redirect with error for web.
     */
    protected function denyAccess(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        $previousUrl = url()->previous();
        $targetUrl = ($previousUrl && $previousUrl !== $request->fullUrl())
            ? $previousUrl
            : route('dashboard');

        return redirect()->to($targetUrl)->with('error', $message);
    }
}
