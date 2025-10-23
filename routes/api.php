<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


/**
 * API Version 1 Routes
 */
Route::prefix('v1')->group(function () {
    
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        
        Route::get('/user', [AuthController::class, 'getCurrentUserDetails']);

        /**
         * User Blog Post Routes
         */
        Route::group(['prefix' => 'user'], function () {
            Route::post('/posts', [PostController::class, 'store']); // create a post
            Route::put('/posts/{post}', [PostController::class, 'update']); // update a post
            Route::get('/posts', [PostController::class, 'index']); // list all posts per user
            Route::get('/posts/{post}', [PostController::class, 'show']);
            Route::delete('/posts/{post}', [PostController::class, 'destroy']);
        });

        /**
         * Comments Routes
         */
        Route::get('/posts/{post}/comments', [CommentController::class, 'index']); // list all comments per post
        Route::post('/posts/{post}/comments', [CommentController::class, 'store']); // create comment
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']); // delete comment

    });

    /**
     * Blog Categories Routes
     */
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::apiResource('categories', CategoryController::class);
    });

});
