<?php

use App\Http\Controllers\QuoteCardController;
use Illuminate\Support\Facades\Route;

Route::post('/posts/{post}/quote-card', QuoteCardController::class)
    ->whereNumber('post')
    ->middleware('throttle:15,1')
    ->name('posts.quote-card');
