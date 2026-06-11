<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1');
Route::get('/setup-status', [AuthController::class, 'setupStatus'])->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());
    Route::get('/users', [AuthController::class, 'users']);
    Route::post('/users', [AuthController::class, 'createUser']);
    Route::post('/backups/monthly', [BackupController::class, 'monthly']);
    Route::get('/dashboard', DashboardController::class);
    Route::get('/catalogs', CatalogController::class);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::get('/transactions-history', [TransactionController::class, 'history']);
    Route::apiResource('/transactions', TransactionController::class)->except(['show']);
    Route::get('/reports/{period}', [ReportController::class, 'show'])
        ->whereIn('period', ['daily', 'weekly', 'monthly', 'annual']);
});
