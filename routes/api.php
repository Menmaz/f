<?php

use App\Http\Controllers\APIs\Auth\ResetPasswordController;
use App\Http\Controllers\APIs\AuthController;
use App\Http\Controllers\APIs\CategoryController;
use App\Http\Controllers\APIs\CommentController;
use App\Http\Controllers\APIs\Contracts\ApiBase;
use App\Http\Controllers\APIs\GoogleAuthController;
use App\Http\Controllers\APIs\MangaController;
use App\Http\Controllers\APIs\MangaDetailController;
use App\Http\Controllers\APIs\UserController;
use App\Http\Controllers\CrawlAPIs\TruyenqqvietCrawlController;
use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(["prefix" => "v1", "as" => "manga."], function () {
    Route::get("/home", [MangaController::class, "index"])->name("index");
    Route::get("/tim-kiem", [MangaController::class, "search"])->name("search");
    Route::get("/the-loai", [CategoryController::class, "getCategories"])->name("getCategories");
    Route::get("/loai", [CategoryController::class, "getTypes"])->name("getTypes");
    Route::get("/trang-thai", [CategoryController::class, "getStatuses"])->name("getStatuses");
    Route::get("/menu", [CategoryController::class, "getMenu"])->name("getMenu");
    Route::get("/the-loai/{categorie}", [MangaController::class, "getMangasByCategory"])->name("getMangasByCategory");
    Route::get("/danh-sach/{categorie}", [MangaController::class, "getMangasByListType"])->name("getMangasByListType");
    Route::get("/lich-truyen", [MangaController::class, "getScheduledMangas"])->name("getScheduledMangas");
    Route::get("/random", [MangaController::class, "getRandomManga"])->name("getRandomManga");
    Route::get("/truyen-tranh/{slug}", [MangaDetailController::class, "getMangaDetail"])->name("getMangaDetail");
    Route::get("/truyen-tranh/{slug}/binh-luan", [MangaDetailController::class, "getComments"])->name("getComments");
    Route::get("/truyen-tranh/{slug}/danh-gia", [MangaDetailController::class, "getRatingInfo"])->name("getRatingInfo");
    Route::get("/truyen-tranh/{slug}/da-luu", [MangaDetailController::class, "isMangaSaved"])->name("isMangaSaved");
    Route::get("/truyen-tranh/{manga_slug}/{chapter}", [MangaDetailController::class, "getChaptersOfManga"])->name("getChaptersOfManga");
    // Route::post('/report/{manga}/{chapter}', [ApiComicController::class, 'reportChapter'])->name('report');
    // Route::post('/report/{manga}', [ApiComicController::class, 'reportManga'])->name('report');
    Route::get("/test", [UserController::class, "test"])->name("test");
    Route::get("/get-bp-image", [UserController::class, "getImageUrlAfterByPass"])->name("getImageUrlAfterByPass");

    //by pass ảnh TruyenVN
    Route::get("/get-bp-truyenvn-image", [UserController::class, "getTruyenVNByPassedImage"]);
    //by pass ảnh TruyenQQViet
    Route::get("/get-bp-truyenqq-image", [UserController::class, "getTruyenQQVietByPassedImage"]);
    
    Route::get('/optimize-image', [CommentController::class, "optimizeImageUrl"])->name("optimizeImageUrl");

    //QUÊN MẬT KHẨU
    Route::post('/password/forgot', [ResetPasswordController::class, 'sendResetLinkEmail']);
    Route::post('/password/verify-otp', [ResetPasswordController::class, 'verifyOtp']);
    Route::post('/password/reset', [ResetPasswordController::class, 'resetPassword']);

    //REFRESH TOKEN
    Route::post('/refresh-token', [UserController::class, 'refreshToken']);
    

    //các chức năng của user
    Route::middleware(['auth:api'])->group(function () {
        //ĐÁNH GIÁ
        Route::post('/rate/{manga_slug}', [UserController::class, 'rate'])->name('rating');
        Route::post('/rate-icon/{manga_slug}', [UserController::class, 'rateByIcon'])->name('iconRating');

        //BÌNH LUẬN
        Route::post('/comment', [UserController::class, 'comment'])->name('comment');
        Route::post('/edit-comment', [UserController::class, 'editComment'])->name('editComment');
        Route::delete('/delete-comment', [UserController::class, 'deleteComment'])->name('deleteComment');
        Route::post('/reply-comment', [UserController::class, 'replyComment'])->name('replyComment');
        Route::post('/like-comment', [UserController::class, 'likeComment'])->name('likeComment');
        Route::post('/dislike-comment', [UserController::class, 'dislikeComment'])->name('dislikeComment');
        Route::post('/report-comment', [UserController::class, 'reportComment'])->name('reportComment');

        //LƯU TRUYỆN
        Route::post("/save-manga", [UserController::class, "saveManga"])->name("saveManga");
        // Route::post("/unsave-manga", [UserController::class, "unsaveManga"])->name("unsaveManga");

        //BÁO CÁO TRUYỆN
        Route::post('/report-manga', [UserController::class, 'reportManga'])->name('reportManga');

        //CÁC THÔNG TIN KHÁC CỦA NGƯỜI DÙNG
        Route::get("/my-bookmarks", [UserController::class, "myBookmarks"])->name("myBookmarks");
        Route::get("/my-comments", [UserController::class, "myComments"])->name("myComments");
        Route::get("/my-notifications", [UserController::class, "myNotifications"])->name("myNotifications");

        //ĐÁNH DẤU ĐÃ XEM THÔNG BÁO
        Route::post('/read-notification', [UserController::class, 'readNotification'])->name('readNotification');
    });
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'v1/auth'

], function ($router) {
    Route::get('/unauthorized', function(){
        return response()->json(
            [
                "status" => false,
                "status_code" => 401,
                "message" => 'Bạn cần đăng nhập để tiếp tục !',
            ],
            401,
        );
    })->name('unauthorized');
    // Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
    // Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');;
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/user-profile/update', [AuthController::class, 'updateUserProfile']);
});

Route::get('/crawler/truyenqqviet/manga_detail', [TruyenqqvietCrawlController::class, 'getMangaDetail']);
Route::get('/crawler/truyenqqviet/manga_detail/chapter', [TruyenqqvietCrawlController::class, 'getChaptersOfManga']);