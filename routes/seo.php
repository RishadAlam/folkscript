<?php

use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/sitemaps/{type}-{page}.xml', [SeoController::class, 'sitemapSection'])->where('type', 'posts|authors|topics')->whereNumber('page')->name('seo.sitemap.section');
Route::get('/feed.xml', [SeoController::class, 'feed'])->name('seo.feed');
Route::get('/@{username}/feed.xml', [SeoController::class, 'feed'])->name('seo.author-feed');
Route::get('/topic/{slug}/feed.xml', [SeoController::class, 'topicFeed'])->name('seo.topic-feed');
Route::get('/indexnow-key.txt', [SeoController::class, 'indexNowKey'])->name('seo.indexnow-key');
