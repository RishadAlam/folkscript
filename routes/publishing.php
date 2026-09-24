<?php

use App\Http\Controllers\EngagementController;
use App\Http\Controllers\PublishingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublishingController::class, 'home'])->name('home');
Route::get('/explore', [PublishingController::class, 'discover'])->name('discover');
Route::get('/trending', [PublishingController::class, 'discover'])->name('trending');
Route::get('/topic/{slug}', [PublishingController::class, 'topic'])->name('topic');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [PublishingController::class, 'dashboard'])->name('dashboard');
    Route::get('/write', [PublishingController::class, 'editor'])->name('posts.create');
    Route::get('/write/{post}', [PublishingController::class, 'editor'])->name('posts.edit');
    Route::post('/posts', [PublishingController::class, 'store'])->middleware('throttle:30,1')->name('posts.store');
    Route::put('/posts/{post}', [PublishingController::class, 'update'])->middleware('throttle:60,1')->name('posts.update');
    Route::delete('/posts/{post}', [PublishingController::class, 'destroy'])->name('posts.destroy');
    Route::post('/media', [PublishingController::class, 'upload'])->middleware('throttle:20,1')->name('media.upload');
    Route::get('/bookmarks', [PublishingController::class, 'bookmarks'])->name('bookmarks');
    Route::get('/notifications', [PublishingController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [PublishingController::class, 'readNotifications'])->name('notifications.read');
    Route::post('/posts/{post}/bookmark', [EngagementController::class, 'bookmark'])->middleware('throttle:60,1')->name('posts.bookmark');
    Route::post('/posts/{post}/react', [EngagementController::class, 'react'])->middleware('throttle:60,1')->name('posts.react');
    Route::post('/posts/{post}/comments', [EngagementController::class, 'comment'])->middleware('throttle:10,1')->name('comments.store');
    Route::delete('/comments/{comment}', [EngagementController::class, 'deleteComment'])->name('comments.destroy');
    Route::post('/posts/{post}/report', [EngagementController::class, 'report'])->middleware('throttle:5,1')->name('posts.report');
    Route::post('/authors/{user}/follow', [EngagementController::class, 'follow'])->middleware('throttle:30,1')->name('authors.follow');
    Route::post('/tags/{tag}/follow', [EngagementController::class, 'followTag'])->middleware('throttle:30,1')->name('tags.follow');
    Route::post('/categories/{category}/follow', [EngagementController::class, 'followCategory'])->middleware('throttle:30,1')->name('categories.follow');
});

Route::get('/@{username}/{slug}', [PublishingController::class, 'article'])->name('article');
Route::get('/@{username}', [PublishingController::class, 'profile'])->name('profile');
