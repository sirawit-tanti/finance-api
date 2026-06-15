<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('admin')->group(function (){
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::post('/admin/users', [AdminUserController::class, 'store']);
        Route::get('/admin/users/export', [AdminUserController::class, 'export']);
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);
        Route::patch('/admin/users/{user}/role', [AdminUserController::class, 'updateRole']);

    });
    Route::get('/me', function(Request $request){
        return $request->user();
    });
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export']);
    Route::get('/categories', [CategoriesController::class, 'index']);
    Route::post('/categories', [CategoriesController::class, 'store']);
    Route::post('/change-password', [ProfileController::class, 'changePassword']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/monthly', [DashboardController::class, 'monthly']);
    Route::get('/dashboard/category', [DashboardController::class, 'category']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/transaction', [TransactionController::class, 'index']);
    Route::post('/transaction', [TransactionController::class, 'store']);
    Route::post('/transaction/bulk', [TransactionController::class, 'bulkStore']);
    Route::get('/transaction/export', [TransactionController::class, 'export']);
    
    Route::put('/categories/{category}', [CategoriesController::class, 'update']);
    Route::delete('/categories/{category}', [CategoriesController::class, 'destroy']);
    Route::get('/transaction/{id}', [TransactionController::class, 'show']);
    Route::put('/transaction/{id}', [TransactionController::class, 'update']);
    Route::delete('/transaction/{id}', [TransactionController::class, 'destroy']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);