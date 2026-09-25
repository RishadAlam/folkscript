<?php

use App\Http\Controllers\ReaderController;
use App\Http\Controllers\ReaderIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/llms.txt', ReaderIndexController::class)->name('reader.index');

Route::prefix('read')->name('reader.')->group(function () {
    Route::get('/stories/{post}.md', [ReaderController::class, 'story'])->whereNumber('post')->name('story');
    Route::get('/authors/{user}.md', [ReaderController::class, 'author'])->whereNumber('user')->name('author');
    Route::get('/collections/{series}.md', [ReaderController::class, 'collection'])->whereNumber('series')->name('collection');
    Route::get('/topics/{slug}.md', [ReaderController::class, 'topic'])->name('topic');
    Route::get('/explore.md', [ReaderController::class, 'explore'])->name('explore');
});
