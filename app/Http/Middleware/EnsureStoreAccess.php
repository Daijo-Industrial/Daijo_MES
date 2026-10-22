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

        $hasAccess = in_array($userRole, $allowedRoles)
            || $user->hasRoleAccess('STORE')
            || $user->hasRoleAccess('ADMIN');

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
