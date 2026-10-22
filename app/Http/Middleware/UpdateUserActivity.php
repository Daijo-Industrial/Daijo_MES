<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            $cacheKey = 'user_activity_ping_' . $user->id;

            // Update database at most once every 5 minutes per active user session
            if (! Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, now()->addMinutes(5));
                $user->updateQuietly(['last_activity_at' => now()]);
            }
        }

        return $response;
    }
}
