<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreAccess
{
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

        $userRole = $user->role ? strtoupper(trim($user->role->name)) : '';

        // Allowed roles: ADMIN, SUPER-ADMIN, STORE, WAREHOUSE
        $allowedRoles = ['ADMIN', 'SUPER-ADMIN', 'STORE', 'WAREHOUSE'];

        // Allow PRODUCTION and PPIC for crosscheck and store handover features
        $isCrosscheckOrHandover = $request->routeIs('wms.production-scan-crosscheck*')
            || $request->routeIs('wms.store-handover*')
            || $request->is('wms/production-scan-crosscheck*')
            || $request->is('wms/store-handover*');

        if ($isCrosscheckOrHandover) {
            $allowedRoles[] = 'PRODUCTION';
            $allowedRoles[] = 'PPIC';
        }

        $hasAccess = in_array($userRole, $allowedRoles)
            || $user->hasRoleAccess('STORE')
            || $user->hasRoleAccess('ADMIN')
            || ($isCrosscheckOrHandover && (
                $user->hasRoleAccess('PRODUCTION')
                || $user->hasRoleAccess('PPIC')
                || $user->can('view-production-links')
                || $user->can('view-ppic-links')
            ));

        if (! $hasAccess) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. Fitur ini hanya dapat diakses oleh role Admin atau Store.',
                ], 403);
            }

            abort(403, 'Akses Ditolak. Halaman ini hanya dapat diakses oleh role Admin atau Store.');
        }

        return $next($request);
    }
}
