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
    Route::post('/posts', [PublishingController::class, 'store'])->middleware('throttle:30,1,posts-create')->name('posts.store');
    Route::put('/posts/{post}', [PublishingController::class, 'update'])->middleware('throttle:60,1,posts-update')->name('posts.update');
    Route::delete('/posts/{post}', [PublishingController::class, 'destroy'])->name('posts.destroy');
    Route::post('/media', [PublishingController::class, 'upload'])->middleware('throttle:20,1,media-upload')->name('media.upload');
    Route::get('/bookmarks', [PublishingController::class, 'bookmarks'])->name('bookmarks');
    Route::get('/notifications', [PublishingController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [PublishingController::class, 'readNotifications'])->name('notifications.read');
    Route::post('/posts/{post}/bookmark', [EngagementController::class, 'bookmark'])->middleware('throttle:60,1,posts-bookmark')->name('posts.bookmark');
    Route::post('/posts/{post}/react', [EngagementController::class, 'react'])->middleware('throttle:60,1,posts-react')->name('posts.react');
    Route::post('/posts/{post}/comments', [EngagementController::class, 'comment'])->middleware('throttle:10,1,posts-comment')->name('comments.store');
    Route::delete('/comments/{comment}', [EngagementController::class, 'deleteComment'])->name('comments.destroy');
    Route::post('/comments/{comment}/report', [EngagementController::class, 'reportComment'])->middleware('throttle:5,1,comments-report')->name('comments.report');
    Route::post('/posts/{post}/report', [EngagementController::class, 'report'])->middleware('throttle:5,1,posts-report')->name('posts.report');
    Route::post('/authors/{user}/follow', [EngagementController::class, 'follow'])->middleware('throttle:30,1,authors-follow')->name('authors.follow');
    Route::post('/tags/{tag}/follow', [EngagementController::class, 'followTag'])->middleware('throttle:30,1,tags-follow')->name('tags.follow');
    Route::post('/categories/{category}/follow', [EngagementController::class, 'followCategory'])->middleware('throttle:30,1,categories-follow')->name('categories.follow');
});

Route::get('/@{username}/{slug}', [PublishingController::class, 'article'])->name('article');
Route::get('/@{username}', [PublishingController::class, 'profile'])->name('profile');
