<?php

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Root redirect
Route::redirect('/', '/login');

// Core Dashboard & Profile
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Auto-login route (development helper, must not interfere with other routes)
Route::get('/auto-login/{user_id}', function ($user_id) {
    $user = User::where('name', $user_id)->first();

    if ($user && $user->is_active) {
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    return redirect('/login')->withErrors(['error' => 'User not found or account is deactivated.']);
})->where('user_id', '[0-9A-Za-z]+');

// ============================================
// MODULAR DOMAIN ROUTE INCLUDES
// ============================================
require __DIR__ . '/web/reports.php';
require __DIR__ . '/web/barcode.php';
require __DIR__ . '/web/production.php';
require __DIR__ . '/web/admin.php';
require __DIR__ . '/web/wms.php';
require __DIR__ . '/web/second_process.php';

// Breeze Auth Routes
require __DIR__ . '/auth.php';
