<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;

//public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// private routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::resource('/post', PostController::class);
    Route::post('/post/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/post/{post}/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');    
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
