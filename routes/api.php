<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('me/student', [ProfileController::class, 'show']);
    Route::put('me/student', [ProfileController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('students/stats', [StudentController::class, 'stats']);

    Route::apiResource('students', StudentController::class);
});
