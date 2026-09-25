<?php
use Illuminate\Support\Facades\Route;
require __DIR__.'/accounts.php';
require __DIR__.'/seo.php';
Route::view('/about', 'about')->name('about');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/terms', 'terms')->name('terms');
require __DIR__.'/quotes.php';
require __DIR__.'/series.php';
require __DIR__.'/reader.php';
require __DIR__.'/platform.php';
Route::post('/admin/users/{user}/support', [\App\Http\Controllers\ImpersonationController::class, 'start'])->middleware(['auth', 'verified', 'password.confirm', 'throttle:5,1,support-start'])->name('support.start');
Route::post('/support/stop', [\App\Http\Controllers\ImpersonationController::class, 'stop'])->middleware('auth')->name('support.stop');
require __DIR__.'/publishing.php';

// Keep the normal web session and navigation when a reader reaches a missing page.
Route::fallback(fn () => abort(404));
