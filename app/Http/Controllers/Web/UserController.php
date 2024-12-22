<?php

namespace App\Http\Controllers\Web;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use Barryvdh\Debugbar\Facades\Debugbar;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ophim\Core\Models\Badge;
use Ophim\Core\Models\Bookmark;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Notification;
use Ophim\Core\Models\StarRating;
use Ophim\Core\Models\Taxonomy;

class UserController extends Controller
{
    function getBadgeInfo()
{
    $userId = auth()->id();
    $userHasBadge = DB::table('users_has_badge')->where('user_id', $userId)->first();

    $badges = [];

    if ($userHasBadge) {
        $currentBadge = Badge::find($userHasBadge->badge_id);

        if ($currentBadge) {
            $badges[] = [
                'name' => $currentBadge->name,
                'image' => $currentBadge->image,
                'cssColor' => $currentBadge->css_color,
                'progress' => 0,
                'totalPoints' => 0,
            ];
        }
    } else {
        $user = auth()->user();
        $totalPoints = $user->views->count();

        $allBadges = Badge::whereNotNull('comment_threshold')
            ->orderBy('comment_threshold', 'asc')
            ->get();

        if ($allBadges->isEmpty()) {
            return ['badges' => []];
        }

        $currentBadge = $allBadges->first();
        foreach ($allBadges as $badge) {
            if ($badge->comment_threshold > $totalPoints) {
                $currentBadge = $badge;
                break;
            }
        }

        $progress = 10;
        if ($currentBadge->comment_threshold > 0) {
            $progress = min(($totalPoints / $currentBadge->comment_threshold) * 100, 99);
        }

        $badges[] = [
            'name' => $currentBadge->name,
            'image' => $currentBadge->image,
            'cssColor' => $currentBadge->css_color,
            'progress' => round($progress, 1), // Rounding progress to one decimal place
            'totalPoints' => $totalPoints,
        ];
    }

    return $badges;
}

    public function profile(){
        $badge = $this->getBadgeInfo();
        return view('frontend-web.user.profile', compact('badge'));
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            if ($request->filled('username')) {
                $user->username = $request->input('username');
            }

            if ($request->filled('email')) {
                $user->email = $request->input('email');
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
            }

            $user->save();

            return response()->json(['status' => 'success', 'message' => 'Cập nhật thông tin thành công !']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function updateAvatar(Request $request){
        $user = Auth::user();
        if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                if ($file->isValid()) {
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        return response()->json([
                            'status' => 'error', 
                            'message' => "Chỉ chấp nhận các định dạng ảnh: JPEG, PNG, hoặc WebP.",
                        ]);
                    }
    
                    if ($file->getSize() > 2 * 1024 * 1024) {
                        return response()->json([
                            'status' => 'error', 
                            'message' => "Kích thước ảnh quá lớn, vui lòng chọn ảnh nhỏ hơn 5MB.",
                        ]);
                    }

                    try {
                        $imageData = $file->path();
                        $userAvatar = ImageHelper::uploadedUserAvatar($user->id, $user->username, $imageData);
                        $user->avatar = $userAvatar;
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => 'error', 
                            'message' => "Lỗi khi xử lý ảnh: " . $e->getMessage(),
                        ]);
                    }
                
                } else {
                    return response()->json([
                        'status' => 'error', 
                        'message' => "File ảnh không hợp lệ.",
                    ]);
                }
                
                $user->save();
                return response()->json([
                        'status' => 'success', 
                        'message' => "Đổi avatar thành công !",
                    ]);
            }
    }
    
    //
    //
    public function reading(){
        return view('frontend-web.user.reading');
    }

    public function removeReadingManga($sessionId)
    {
        $readingMangas = session('reading_mangas', []);
        foreach ($readingMangas as $index => $readingManga) {
            if ($readingManga['session_id'] == $sessionId) {
                unset($readingMangas[$index]);
                break;
            }
        }
        session(['reading_mangas' => array_values($readingMangas)]);

        return response()->json(['success' => true]);
    }

    public function clearReadingMangas()
    {
        session()->forget('reading_mangas');
        return response()->json(['success' => true]);
    }

    //
    //
    public function getStatus()
    {           
        return Cache::remember('statuses', now()->addHours(8), function () {
            return Taxonomy::where("type", 'status')->orderBy('name', 'asc')->get(["name", "slug", "seo_des as description"]);
        });
    }

    public function getCategories()
    {           
        return Cache::remember('categories_in_filter', now()->addHours(8), function () {
            return Taxonomy::where("type", 'genre')->orderBy('name', 'asc')->get();
        });
    }

    public function bookmark(Request $request){
        $sort_by = $sort ?? 'default';

        $folder = request()->input('folder');

        $bookmarksQuery = auth()->user()->bookmarks();

        if ($folder) {
            $bookmarksQuery->where('status', $folder);
        }

        $categories = $this->getCategories();
        $statuses  = $this->getStatus();

        $bookmarks = $bookmarksQuery->orderBy('created_at', 'desc')->get();
        
        return view('frontend-web.user.bookmark', compact('bookmarks', 'categories', 'statuses', 'sort_by'));
    }

    public function saveBookmark(Request $request)
    {
        try {
            if ($request->action === 'delete') {
                Bookmark::where('user_id', auth()->id())
                    ->where('bookmarkable_id', $request->manga_id)
                    ->delete();
    
                return response()->json(['success' => true, 'message' => 'Đã hủy lưu truyện !']);
            }   
                    
        $bookmark = Bookmark::updateOrCreate(
            ['user_id' => auth()->id(), 'bookmarkable_id' => $request->manga_id, 'bookmarkable_type' => Manga::class],
            ['status' => $request->action]
        );

        if ($bookmark) {
            return response()->json(['success' => true, 'message' => 'Lưu truyện thành công !']);
        } else {
            return response()->json(['success' => false, 'message' => 'Lưu truyện thành công !']);
        }
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage() ]);
        }
    }

    public function updateBookmarkStatus(Request $request)
    {
        $bookmarkId = $request->input('bookmark_id');
        $status = $request->input('status');

        $bookmark = Bookmark::findOrFail($bookmarkId);
        $bookmark->status = $status;
        $bookmark->save();

        return response()->json(['success' => true, 'status' => $status]);
    }

    public function deleteBookmark(Request $request)
    {
        $bookmarkId = $request->input('bookmark_id');
        $bookmark = Bookmark::findOrFail($bookmarkId);
        $bookmark->delete(); 

        return response()->json(['success' => true]);
    }

    public function filterBookmark(Request $request)
{
    try {
        $keyword = $request->input('keyword');
        $types = $request->input('type', []);
        $genres = $request->input('genre', []);
        $genre_mode = $request->input('genre_mode');
        $statuses = $request->input('status', []);
        $years = $request->input('year', []);
        $sort = $request->input('sort');

        $categories = Cache::remember('categories_in_filter', now()->addHours(8), function () {
            return Taxonomy::where("type", 'genre')->orderBy('name', 'asc')->get();
        });

        $statuses2 = Cache::remember('statuses', now()->addHours(8), function () {
            return Taxonomy::where("type", 'status')->orderBy('name', 'asc')->get(["name", "slug"]);
        });

        $user = Auth::user();

        $query = Bookmark::query();

        $query->where('user_id', $user->id);

        // Nạp thông tin của manga liên quan
        $query->with('manga:id,title,slug,cover');

        // Lọc theo từ khóa tìm kiếm trong tiêu đề manga
        if ($keyword) {
            $query->whereHas('manga', function ($subQuery) use ($keyword) {
                $subQuery->where('title', 'LIKE', '%' . $keyword . '%');
            });
        }

        // Lọc theo loại (taxonomy type)
        if (!empty($types) && is_array($types)) {
            $query->whereHas('manga.taxanomies', function ($subQuery) use ($types) {
                $subQuery->whereIn('slug', $types);
            });
        }

        // Lọc theo thể loại (taxonomy genre)
        if (!empty($genres) && is_array($genres)) {
            $query->whereHas('manga.taxanomies', function ($subQuery) use ($genres, $genre_mode) {
                if ($genre_mode) {
                    $subQuery->whereIn('slug', $genres)->havingRaw('COUNT(*) = ' . count($genres));
                } else {
                    $subQuery->whereIn('slug', $genres);
                }
            });
        }

        // Lọc theo trạng thái (status)
        if (!empty($statuses) && is_array($statuses)) {
            $query->whereHas('manga.taxanomies', function ($subQuery) use ($statuses) {
                $subQuery->whereIn('slug', $statuses);
            });
        }

        // Sắp xếp
        if ($sort) {
            switch ($sort) {
                case 'recently_updated':
                    $query->orderBy('updated_at', 'desc');
                    break;
                case 'recently_added':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'title_az':
                    $query->whereHas('manga', function ($subQuery) {
                        $subQuery->orderBy('title', 'asc');
                    });
                    break;
                case 'scores':
                    $query->whereHas('manga', function ($subQuery) {
                        $subQuery->withAvg('starRatings', 'rating')
                            ->orderByDesc('star_ratings_avg_rating');
                    });
                    break;
                case 'most_viewed':
                    $query->whereHas('manga', function ($subQuery) {
                        $subQuery->withSum('views', 'views')
                        ->orderByDesc('views_sum_views');
                    });
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Lấy kết quả với số lượng giới hạn
        $bookmarks = $query->get();

        $data = [
            'sort_by' => $sort ?? 'recently_updated',
            'categories' => $categories,
            'types_selected' => $types,
            'genres_selected' => $genres,
            'genre_mode' => $genre_mode,
            'statuses_selected' => $statuses,
            'years_selected' => $years,
            'statuses' => $statuses2,
            'bookmarks' => $bookmarks
           ];

        // Trả về view với dữ liệu được truyền đi
        return view('frontend-web.user.bookmark')->with($data);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()], 500);
    }
}

    public function starRating(Request $request)
    {
        $mangaId = intval($request->input('manga_id'));
        $ratingValue = intval($request->input('rating'));

        if ($ratingValue < 1 || $ratingValue > 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'Giá trị đánh giá sao phải từ 1 đến 5',
            ]);
        }
            $existingRating = StarRating::where('manga_id', $mangaId)
                ->where('user_id', auth()->id())
                ->first();

                if ($existingRating) {
                    $existingRating->update(['rating' => $ratingValue]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Đánh giá sao thành công !',
                    ]);
                }

             StarRating::create([
                'user_id' => auth()->id(),
                'manga_id' => $mangaId,
                'rating' => $ratingValue
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Đánh giá sao thành công !',
            ]);
    }

    //
    //
    public function notification(){
        return view('frontend-web.user.notification');
    }

    public function getNotifications(){
        $currentTime = now();
        $user_id = auth()->id();
        $bookmarks = auth()->user()->bookmarks->where('bookmarkable_type', Manga::class)->pluck('bookmarkable_id');

        foreach ($bookmarks as $mangaId) {
            // Kiểm tra sự tồn tại của thông báo dựa trên manga_id và message
            $existingNotification = Notification::where('user_id', $user_id)
            ->where('manga_id', $mangaId)
            ->where('message', 'LIKE', 'Đã có chapter mới%')
            ->first();

            // Kiểm tra xem truyện có chapter mới không
            $newChapter = Chapter::where('manga_id', $mangaId)
                ->latest()
                ->first();

            if ($newChapter && $newChapter->created_at > $currentTime) {
                // Nếu chapter mới nhất được tạo sau thời điểm người dùng truy cập web, tạo thông báo
                if (!$existingNotification) {
                $notification = new Notification([
                    'user_id' => $user_id,
                    'manga_id' => $mangaId,
                    'message' => 'Đã có chapter mới: Chapter' . $newChapter->chapter_number,
                ]);
                }
                $notification->save();
            }
        }

        // Lấy 10 thông báo chưa đọc mới nhất
        $notifications = auth()->user()->notifications()
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($notifications);
    }

    public function readNotifications(Request $request)
    {
        $user = auth()->user();
        
        // Update all unread notifications for the authenticated user
        $updated = Notification::where('user_id', $user->id)
            ->where('status', 0) // Assuming status 0 means unread
            ->update(['status' => 1]); // Update status to 1 (read)

        if ($updated) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 500);
    }

    //
    //
    public function settings(){
        return view('frontend-web.user.settings');
    }

}
