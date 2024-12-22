<?php

use Illuminate\Support\Facades\Route;
use CKSource\CKFinderBridge\Controller\CKFinderController;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'Ophim\Core\Controllers\Admin',
], function () {
    if (config('backpack.base.setup_dashboard_routes')) {
        Route::get('dashboard', 'AdminController@dashboard')->name('backpack.dashboard');
        // Route::get('dashboard-info', 'AdminController@getDashboardInfo');
        Route::get('dashboard-counter-info', 'AdminController@getDashboardCounterInfo');
        Route::get('dashboard-mangas-info', 'AdminController@getDashboardMangasInfo');
        Route::get('/', 'AdminController@redirect')->name('backpack');
    }

    Route::crud('catalog', 'TypeCrudController');
    Route::crud('category', 'CategoryCrudController');
    Route::crud('manga', 'MangaCrudController');
    Route::get('manga/crawl_chapter/{manga_slug}', 'MangaCrudController@showCrawlChapterPage');
    Route::get('manga/crawl_chapter/{manga_slug}/fetch', 'MangaCrudController@fetchChapters');
    Route::post('manga/crawl_chapter/{manga_slug}/{chapter_number}/crawl', 'MangaCrudController@crawlChapters');
    Route::crud('chapters/{manga_slug}', 'ChaptersOfMangaCrudController');
    Route::post('chapters/{manga_slug}/upload-images', 'ChaptersOfMangaCrudController@uploadImages');
    // Route::crud('chapters/crawl_chapter/{manga_slug}/{chapter_id}', 'CrawlChapterCrudController');
    Route::crud('chapter', 'ChapterCrudController');
    Route::crud('comment', 'CommentCrudController');
    Route::crud('comment_report', 'CommentReportCrudController');
    Route::crud('star_rating', 'StarRatingCrudController');
    Route::crud('icon_rating', 'IconRatingCrudController');
    Route::crud('chapter_report', 'ChapterReportCrudController');
    Route::crud('sitemap', 'SiteMapController');
    Route::crud('badge', 'BadgeCrudController');
    Route::get('quick-action/delete-cache', 'QuickActionController@delete_cache');
    Route::get('eth', 'ChapterCrudController@eth');
    Route::crud('ads', 'AdsCrudController');
    Route::crud('terms', 'TermsCrudController');
    Route::post('terms/update', 'TermsCrudController@update')->name('terms.update');

    // Route::crud('region', 'RegionCrudController');
    // Route::crud('actor', 'ActorCrudController');
    // Route::crud('director', 'DirectorCrudController');
    // Route::crud('studio', 'StudioCrudController');
    // Route::crud('tag', 'TagCrudController');
    // Route::crud('menu', 'MenuCrudController');
    // Route::crud('episode', 'EpisodeCrudController');
    // Route::crud('theme', 'ThemeManagementController');
    // Route::crud('sitemap', 'SiteMapController');
});

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        [
            \Ophim\Core\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class
        ],
        (array) config('backpack.base.middleware_key', 'admin')
    ),
], function () {
    Route::prefix('/ckfinder')->group(function () {
        Route::any('/connector', [CKFinderController::class, 'requestAction'])->name('ckfinder_connector');
        Route::any('/browser', [CKFinderController::class, 'browserAction'])->name('ckfinder_browser');
    });
});
