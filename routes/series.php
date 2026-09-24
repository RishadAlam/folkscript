<?php

use App\Http\Controllers\SeriesController;
use Illuminate\Support\Facades\Route;

Route::get('/@{username}/series/{slug}', [SeriesController::class, 'show'])->name('series.show');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
    Route::get('/series/{series}/edit', [SeriesController::class, 'index'])->name('series.edit');
    Route::post('/series', [SeriesController::class, 'store'])->middleware('throttle:10,1')->name('series.store');
    Route::patch('/series/{series}', [SeriesController::class, 'update'])->name('series.update');
    Route::delete('/series/{series}', [SeriesController::class, 'destroy'])->name('series.destroy');
    Route::post('/series/{series}/posts', [SeriesController::class, 'attach'])->name('series.posts.attach');
    Route::delete('/series/{series}/posts/{post}', [SeriesController::class, 'detach'])->name('series.posts.detach');
    Route::patch('/series/{series}/order', [SeriesController::class, 'reorder'])->name('series.order');
});
