<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1,api-v1')->group(function () {
    Route::get('/posts', [ApiController::class, 'posts']);
    Route::get('/posts/{post}', [ApiController::class, 'show'])->whereNumber('post');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [ApiController::class, 'me']);
        Route::get('/me/posts', [ApiController::class, 'ownPosts']);
        Route::delete('/tokens/{token}', [ApiController::class, 'revokeToken'])->whereNumber('token');
    });
});
