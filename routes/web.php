<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ChapterController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\MangaDetailController;
use App\Http\Controllers\Web\MangaListController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

//HOME CONTROLLER
Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('ajax/home/widget/search', 'search')->name('ajax.search');
    Route::get('ajax/home/widget/updated-mangas/{type}', 'fetchUpdatedMangas')->name('ajax.updated_mangas');
    Route::post('ajax/send-manga-request', 'sendMangaRequest')->name('ajax.send-manga-request');
});

//MANGA LIST CONTROLLER
Route::controller(MangaListController::class)->group(function () {
    Route::get('tim-kiem', 'filter')->name('mangas.filter');
    Route::get('the-loai/{category_slug}', 'showMangasByCategory')->name('mangas.category');
    Route::get('truyen-moi', 'showLatestMangas')->name('mangas.latest');
    Route::get('moi-cap-nhat', 'showLatestUpdatedMangas')->name('mangas.latest-updated');
    Route::get('lich-truyen', 'getScheduledMangas')->name('mangas.scheduled');
});

//MANGA DETAIL CONTROLLER
Route::controller(MangaDetailController::class)->group(function () {
    Route::get('truyen/{slug}', 'index')->name('manga.detail');
    Route::get('ajax/chapter/widget/pages/{manga_slug}/{chapter_number}', 'fetchPages')->name('chapter.ajax.pages');
    Route::get('truyen/{slug}/chapters', 'fetchChapters')->name('manga.fetchChapters');
    Route::get('random', 'random')->name('manga.random');
});


//CONTACT CONTROLLER
Route::controller(ContactController::class)->group(function () {
Route::get('contact', 'index')->name('contact');
Route::post('send-contact', 'sendContact')->name('ajax.send-contact');
Route::get('chinh-sach-bao-mat', 'terms')->name('terms');
});

//CHAPTER CONTROLLER
Route::controller(ChapterController::class)->group(function () {
    Route::get('doc-truyen/{slug}/{chapter_number}', 'index')->name('manga.chapter');
    Route::post('ajax/chapter/report', [ChapterController::class, 'reportChapter'])->name('chapter.report');
});

//AUTH CONTROLLER
Route::controller(AuthController::class)->middleware('doNotCacheResponse')->group(function () {
    Route::post('ajax/user/login', 'login')->name('ajax.login');
    Route::post('ajax/user/register', 'register')->name('ajax.register');
});


Route::group(['prefix' => 'user', 'middleware' => 'web.auth'], function () {
    //USER CONTROLLER
    Route::controller(UserController::class)->group(function () {
        Route::post('save-bookmark', 'saveBookmark')->name('user.save-bookmark');
        Route::post('update-bookmark-status', 'updateBookmarkStatus')->name('user.update-bookmark-status');
        Route::post('delete-bookmark', 'deleteBookmark')->name('user.delete-bookmark');
        Route::get('filter-bookmark', 'filterBookmark')->name('user.filter-bookmark');
        Route::get('star-rating', 'starRating')->name('user.star-rating');
        Route::get('profile', 'profile')->name('user.profile');
        Route::post('update-profile', 'updateProfile')->name('user.update-profile');
        Route::post('update-avatar', 'updateAvatar')->name('user.update-avatar');
        Route::get('reading', 'reading')->name('user.reading');
        Route::delete('remove-reading-manga/{session_id}', 'removeReadingManga')->name('user.remove-reading-manga');
        Route::delete('/clear-reading-mangas', 'clearReadingMangas')->name('user.clear-reading-mangas');
        Route::get('bookmark', 'bookmark')->name('user.bookmark');
        Route::get('notification', 'notification')->name('user.notification');
        Route::get('ajax/get-notifications', 'getNotifications')->name('user.get-notifications');
        Route::post('ajax/read-notifications', 'readNotifications')->name('user.read-notifications');
        Route::get('settings', 'settings')->name('user.settings');
    });
    Route::get('logout', [AuthController::class, 'logout'])->middleware('doNotCacheResponse')->name('user.logout');
});

Route::get('test-gpt/{content}', function($content) {
    $api_key = 'sk-proj-ESFdTyqvnFzODr9IR9CbT3BlbkFJZ6HLrH6aq4gbU8ZFx0Zw';
    $url = 'https://api.openai.com/v1/chat/completions';

    $data = [
        'model' => 'gpt-3.5-turbo-0125',
        'max_tokens' => 1000,
        'temperature' => 0.7,
        'messages' => [
            [
                'role' => 'user',
                'content' => "
                    $content
    
                    Đổi nội dung trên sang nội dung tương tự để tránh đạo văn, nếu đã đổi xong thì thêm đoạn là '- " . config('custom.frontend_domain') . "' vào cuối đoạn văn
                "]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key, // Set authorization header
    ]);

    $response = curl_exec($ch);
    if(curl_errno($ch)){
        echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);
    $result = json_decode($response, true);

    return $result; 
});


use App\Helpers\ImageHelper;
//by pass ảnh truyentranh.net.vn
Route::get('api/get-bp-truyentranhnet-image', function () {
    $imageUrl = request()->image_url;
    $referrer = 'https://truyentranh.net.vn/';
    return ImageHelper::getImageUrlAfterByPass($imageUrl, $referrer);
});

use App\Http\Controllers\APIs\UserController as APIUserController;
Route::get('get-bp-image', [APIUserController::class, 'getBPImage'])->name('get-bp-image');