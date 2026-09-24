<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MembershipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (Request $request, string $token) => view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');
    Route::view('/two-factor-challenge', 'auth.two-factor')->name('two-factor.login');
    Route::post('/two-factor-challenge', [AuthController::class, 'twoFactor'])->middleware('throttle:6,1');
    Route::get('/auth/{provider}/redirect', [AuthController::class, 'socialRedirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'socialCallback'])->name('social.callback');
});

Route::get('/membership', [MembershipController::class, 'index'])->name('membership');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::view('/email/verify', 'auth.verify-email')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerification'])->middleware('throttle:3,1')->name('verification.send');
    Route::view('/confirm-password', 'auth.confirm-password')->name('password.confirm');
    Route::post('/confirm-password', [AuthController::class, 'confirmPassword'])->middleware('throttle:6,1');
    Route::get('/settings', [AccountController::class, 'edit'])->name('settings');
    Route::post('/settings/pinned-story', [AccountController::class, 'pinStory'])->middleware('verified')->name('settings.pinned-story');
    Route::patch('/settings', [AccountController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [AccountController::class, 'password'])->middleware('throttle:6,1')->name('settings.password');
    Route::delete('/settings/account', [AccountController::class, 'destroy'])->middleware('throttle:3,1')->name('settings.destroy');
    Route::middleware('password.confirm')->group(function () {
        Route::post('/settings/two-factor', [AccountController::class, 'enableTwoFactor'])->name('settings.two-factor.enable');
        Route::post('/settings/two-factor/confirm', [AccountController::class, 'confirmTwoFactor'])->middleware('throttle:6,1')->name('settings.two-factor.confirm');
        Route::delete('/settings/two-factor', [AccountController::class, 'disableTwoFactor'])->name('settings.two-factor.disable');
        Route::post('/settings/recovery-codes', [AccountController::class, 'recoveryCodes'])->name('settings.recovery-codes');
        Route::post('/settings/tokens', [AccountController::class, 'createToken'])->middleware('verified')->name('settings.tokens.create');
    });
    Route::delete('/settings/tokens/{token}', [AccountController::class, 'revokeToken'])->name('settings.tokens.revoke');
    Route::post('/membership/checkout', [MembershipController::class, 'checkout'])->middleware(['verified', 'throttle:6,1'])->name('membership.checkout');
    Route::post('/membership/portal', [MembershipController::class, 'portal'])->name('membership.portal');
    Route::post('/membership/connect', [MembershipController::class, 'connect'])->middleware(['verified', 'throttle:6,1'])->name('membership.connect');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::patch('/admin/users/{user}', [AdminController::class, 'user'])->name('admin.users.update');
    Route::patch('/admin/reports/{report}', [AdminController::class, 'report'])->name('admin.reports.update');
    Route::patch('/admin/comments/{comment}', [AdminController::class, 'comment'])->name('admin.comments.update');
    Route::patch('/admin/posts/{post}', [AdminController::class, 'post'])->name('admin.posts.update');
    Route::post('/admin/taxonomy/{type}', [AdminController::class, 'taxonomy'])->name('admin.taxonomy.store');
});
