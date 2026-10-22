<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class LogAuthenticationActivity
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event): void
    {
        $user = $event->user;

        if ($user) {
            // Update last_activity_at silently to avoid firing extra Eloquent events
            $user->updateQuietly(['last_activity_at' => now()]);

            activity('auth')
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'guard' => $event->guard,
                ])
                ->log("User logged in ({$user->name})");
        }
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log("User logged out ({$user->name})");
        }
    }

    /**
     * Handle failed login attempts.
     */
    public function handleUserFailed(Failed $event): void
    {
        $credentials = $event->credentials ?? [];
        // Strictly exclude password from logs to comply with GDPR & ISO 27001
        unset($credentials['password'], $credentials['password_confirmation']);

        $identifier = $credentials['email'] ?? $credentials['username'] ?? 'Unknown User';

        activity('auth')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'credentials' => $credentials,
            ])
            ->log("Failed login attempt for {$identifier}");
    }

    /**
     * Handle lockout events.
     */
    public function handleUserLockout(Lockout $event): void
    {
        activity('auth')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'email' => request()->input('email', request()->input('form.email')),
            ])
            ->log('Login lockout triggered');
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleUserLogin',
            Logout::class => 'handleUserLogout',
            Failed::class => 'handleUserFailed',
            Lockout::class => 'handleUserLockout',
        ];
    }
}
