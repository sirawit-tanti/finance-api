<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function(Request $request){
        return $request->user();
    });
    Route::get('/transaction', [TransactionController::class, 'index']);
    Route::post('/transaction', [TransactionController::class, 'store']);
    Route::get('/transaction/{id}', [TransactionController::class, 'show']);
    Route::put('/transaction/{id}', [TransactionController::class, 'update']);
    Route::delete('/transaction/{id}', [TransactionController::class, 'destroy']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/monthly', [DashboardController::class, 'monthly']);
    Route::get('/dashboard/category', [DashboardController::class, 'category']);
    Route::get('/categories', [CategoriesController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);