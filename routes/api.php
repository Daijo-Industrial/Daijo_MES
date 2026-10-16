<?php

use App\Http\Controllers\Api\CustomerProductionController;
use App\Http\Controllers\MasterListItemController;
use App\Http\Controllers\Store\SOController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['ip.and.apikey', 'throttle:sap-api'])->group(function () {
    Route::post('/sap/do', [SOController::class, 'storeFromSap']);
});

Route::middleware(['ip.and.apikey', 'throttle:sap-api'])->group(function () {
    Route::post('/sap/spk', [SOController::class, 'storeSpkNew']);
});

Route::middleware(['ip.and.apikey', 'throttle:sap-api'])->group(function () {
    Route::post('/sap/master-list-item', [MasterListItemController::class, 'storeFromSap']);
});

Route::get(
    '/production-status-range',
    [CustomerProductionController::class, 'range']
);
