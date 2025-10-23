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
    
    /**
     * Public Blog Post Routes
     */
    Route::get('/posts', [PostController::class, 'allPosts']); // list all posts
    Route::get('/posts/{post}', [PostController::class, 'show']); // get single post details
    
    /**
     * Public Category Routes
    */
    Route::get('/categories', [CategoryController::class, 'index']); // list all categories
    Route::get('/categories/{category}', [CategoryController::class, 'show']); // get single category details
    Route::get('/categories/{category}/posts', [PostController::class, 'postsByCategory']); // get posts by category
    
    /**
    * Public Comments Routes
    */
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']); // list all comments per post
    
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
            Route::delete('/posts/{post}', [PostController::class, 'destroy']);
            Route::patch('/posts/{post}/toggle-publish', [PostController::class, 'togglePublish']); // toggle publish status
        });
        
        
        /**
         * Comments Routes
         */
        Route::post('/posts/{post}/comments', [CommentController::class, 'store']); // create comment
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']); // delete comment

    });

    /**
     * Blog Categories Routes
     */
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']); // create category
        Route::put('/categories/{category}', [CategoryController::class, 'update']); // update category
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']); // delete category
    });

});
