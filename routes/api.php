<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoanDueController;
use App\Http\Controllers\LoanCategoryController;
use App\Http\Controllers\LoanController;


// User Registration
Route::post('/register', [AuthController::class, 'register']);
Route::put('/user-update/{id}', [AuthController::class, 'update']);

// User Login
Route::post('/login', [AuthController::class, 'login']);
Route::get('/profile/{id}', [AuthController::class, 'profile']);
Route::get('/all-users', [AuthController::class, 'allUsers']);
Route::get('/employees', [AuthController::class, 'getEmployees']);
Route::get('/getusers', [AuthController::class, 'getUsers']);
Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);
Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);

Route::get('/test-token', [AuthController::class, 'testTokenGeneration']);

Route::resource('/loan-due', LoanDueController::class);
Route::resource('/loan-category', LoanCategoryController::class);
Route::resource('/loan', LoanController::class);
