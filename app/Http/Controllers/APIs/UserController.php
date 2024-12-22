<?php

namespace App\Http\Controllers\APIs;

use App\Helpers\AccountHelper;
use App\Helpers\ImageHelper;
use App\Http\Controllers\APIs\Contracts\ApiBase ;
use App\Http\Resources\CommentResource;
use App\Http\Resources\MangaResource;
use App\Http\Resources\NotificationResource;
use App\Models\RefreshToken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ophim\Core\Controllers\Admin\ImageStorageManager;
use Ophim\Core\Models\Bookmark;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\ChapterReport;
use Ophim\Core\Models\Comment;
use Ophim\Core\Models\CommentReaction;
use Ophim\Core\Models\CommentReport;
use Ophim\Core\Models\IconRating;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Notification;
use Ophim\Core\Models\StarRating;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class UserController extends ApiBase
{

    public function __construct()
    {
    }

    //hàm kiểm tra các hạn chế của tài khoản 
    protected function checkUserRestrictions($user_id, $action)
   {    // Kiểm tra nếu tài khoản bị khóa
        if (AccountHelper::isAccountLocked($user_id)) {
            return $this->response(['message' => 'Tài khoản của bạn đã bị khóa.'], 403);  // 403 Forbidden
        }

        // Lưu hành động của tài khoản
        AccountHelper::captureUserAction($user_id, $action);

        // Kiểm tra xem người dùng có spam không và khóa tài khoản nếu cần
        $spamThreshold = 5;  // Ví dụ: Ngưỡng số lần bình luận để coi là spam
        $spamTimeWindow = 60 * 2;  // Ví dụ: Khoảng thời gian 2 phút
        $isSpam = AccountHelper::lockAccountIfSpam($user_id, $action, $spamThreshold, $spamTimeWindow);
        if ($isSpam) {
            return $this->response(['message' => 'Tài khoản của bạn đã bị khóa do spam!'], 403);  // 403 Forbidden
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | ĐÁNH GIÁ
    |--------------------------------------------------------------------------
    */

    //đánh giá sao
    public function rate(Request $request)
{
    $ratingValue = intval($request->input('rating'));

    if ($ratingValue < 1 || $ratingValue > 5) {
        return $this->response(['message' => 'Giá trị đánh giá sao phải từ 1 đến 5'], 400);
    }

    try {
        $manga = Manga::where('slug', $request->manga_slug)->firstOrFail(); 

        $existingRating = StarRating::where('manga_id', $manga->id)
            ->where('user_id', auth()->id())
            ->first();

            if ($existingRating) {
                $existingRating->update(['rating' => $ratingValue]);
                return $this->response(['message' => 'Đánh giá sao thành công !', 'data' => $existingRating], 200);
            }

        $rating = new StarRating([
            'user_id' => auth()->id(),
            'rating' => $ratingValue
        ]);

        $manga->starRatings()->save($rating);

        return $this->response(['message' => 'Đánh giá sao thành công !', 'data' => $rating], 200);
    } catch (\Throwable $th) {
        return $this->response(['message' => $th->getMessage()], 500);
    }
}

    //đánh giá icon
    public function rateByIcon(Request $request)
{
    try {
        $manga = Manga::where('slug', $request->manga_slug)->firstOrFail();
        $icon = $request->icon;
        $icons = ['like', 'buon-cuoi', 'tuyet-voi', 'ngac-nhien', 'buon', 'tuc-gian'];

        if (!in_array($icon, $icons)) {
            return $this->response(['message' => 'Icon không hợp lệ.'], 400);
        }

        $existingRating = IconRating::where('manga_id', $manga->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingRating) {
            $existingRating->update(['icon' => $icon]);
            return $this->response(['message' => 'Đánh giá bằng icon thành công !', 'data' => $existingRating], 200);
        }

        $rating = new IconRating([
            'user_id' => auth()->id(),
            'icon' => $request->icon
        ]);

        $manga->iconRatings()->save($rating);

        return $this->response(['message' => 'Đánh giá bằng icon thành công !', 'data' => $rating], 200);
    } catch (\Throwable $th) {
        return $this->response(['message' => $th->getMessage()], 500);
    }
}



    /*
    |--------------------------------------------------------------------------
    | BÌNH LUẬN
    |--------------------------------------------------------------------------
    */

    protected function checkCommentPrerequisites($user_id, $content, $action)
    {
        // $preCheckUserRestrictionsResponse  = $this->checkUserRestrictions($user_id, 'comment');
        // if ($preCheckUserRestrictionsResponse ) {
        //     return $preCheckUserRestrictionsResponse;
        // }

        // Kiểm tra xem nội dung có chứa từ ngữ tục tĩu hoặc liên kết không mong muốn không
        if (AccountHelper::isOffensiveOrContainsUnwantedLinks($content)) {
            return $this->response(['message' => 'Bình luận chứa nội dung thiếu lành mạnh hoặc liên kết quảng cáo!'], 400); // 400 Bad Request
        }
        
        // No issues, return null to indicate success
        return null;
    }

    //bình luận
    public function comment(Request $request)
    {
        try {
            $manga_slug = $request->input('manga_slug');
            $content = $request->input('content');
            $user_id = auth()->id();

            $manga = Manga::where('slug', $manga_slug)->first(); 
            if (!$manga) {
                return $this->response(['message' => 'Truyện không tồn tại!'], 404);  // 404 Not Found
            }

            //kiểm tra nâng cao
            $preCheckCommentResponse  = $this->checkCommentPrerequisites($user_id, $content, 'comment');
            if ($preCheckCommentResponse) {
                return $preCheckCommentResponse ;
            }

            $comment = new Comment([
                'user_id' => $user_id,
                'commentable_type' => Manga::class,
                'content' => $content
            ]);

            $manga->comments()->save($comment);

            return $this->response(['message' => 'Bình luận thành công !', 'data' => $comment], 200);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //sửa bình luận
    public function editComment(Request $request)
{
    try {
        $comment_id = intval($request->input('id'));
        $comment = Comment::findOrFail($comment_id);
        $content = $request->input('content');
        $user_id = auth()->id();

        if ($comment->user_id !== $user_id) {
            return $this->response(['message' => 'Bạn không có quyền chỉnh sửa bình luận này.'], 403);
        }

        //kiểm tra nâng cao
        $preCheckCommentResponse  = $this->checkCommentPrerequisites($user_id, $content, 'comment');
        if ($preCheckCommentResponse) {
            return $preCheckCommentResponse ;
        }

        $comment->content = $content;
        $comment->save();

        return $this->response(['message' => 'Bình luận đã được cập nhật thành công!', 'data' => $comment], 200);
    } catch (\Throwable $th) {
        return $this->response(['message' => 'Có lỗi xảy ra: ' . $th->getMessage()], 500);
    }
}


    //xóa bình luận
    public function deleteComment(Request $request)
    {
        try {
            $comment_id = intval($request->id);

            if (empty($comment_id)) {
                return $this->response(['message' => 'Chưa truyền id của bình luận bạn muốn xóa'], 400);
            }

            $user_id = auth()->user()->id;
            $comment = Comment::where('id', $comment_id)->first();

            if (!$comment) {
                return $this->response(['message' => 'Comment not found'], 404);
            }

            // Kiểm tra xem người dùng có quyền xóa bình luận này không
            if ($comment->user_id != $user_id && !auth()->user()->is_admin) {
                return $this->response(['message' => 'Bạn không có quyền xóa bình luận này !'], 403);
            }

            // Xóa tất cả các phản ứng liên quan đến bình luận chính và các bình luận con
            CommentReaction::where('comment_id', $comment_id)->delete();
            
            // Xóa các bình luận con có parent_id là comment_id
            Comment::where('parent_id', $comment_id)->delete();

            // Xóa bình luận chính
            $comment->delete();

            return $this->response(['message' => 'Đã xóa bình luận và các bình luận liên quan khác'], 200);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //phản hồi bình luận
    public function replyComment(Request $request)
    {
        try {
            $manga_slug = $request->input('manga_slug');
            $parent_id = intval($request->input('id'));
            $parentComment = Comment::find($parent_id);
            if(!$parentComment){
                return $this->response(['message' => 'Không tìm thấy bình luận !'], 404); 
            }
            
            $content = $request->input('content');
            $user_id = auth()->id();
            $manga = $parentComment->manga()->first(); 
           
            //kiểm tra nâng cao
            $preCheckCommentResponse  = $this->checkCommentPrerequisites($user_id, $content, 'comment');
            if ($preCheckCommentResponse) {
                return $preCheckCommentResponse ;
            }

            $comment = new Comment([
                'user_id' => $user_id,
                'commentable_type' => Manga::class,
                'content' => $content,
                'parent_id' => $parent_id,
            ]);
            

            $manga->comments()->save($comment);

            // Tạo thông báo cho người đã đăng bình luận gốc
            $parentCommentOwner = $parentComment->user;
            if ($parentCommentOwner->id != $user_id) {
                $parentCommentOwner->notifications()->create([
                    'message' => auth()->user()->username . " đã trả lời bình luận của bạn",
                    'type' => 'reply',
                    'manga_id' => $manga->id,
                    'comment_id' => $comment->id,
                    'related_user_id' => $user_id
                ]);
            }

            return $this->response(['message' => 'Phản hồi bình luận thành công !', 'data' => $comment], 200);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //thích bình luận
    public function likeComment(Request $request)
    {
        try {
            $comment_id = intval($request->input('id'));
            $user_id = auth()->id();
            $comment = Comment::find($comment_id);
            $commentOwner = $comment->user;

            // Kiểm tra xem người dùng đã "like" bình luận này chưa
            $likedComment = CommentReaction::where('user_id', $user_id)
                ->where('comment_id', $comment_id)
                ->where('type', 1) // "like"
                ->first();

            if ($likedComment) {
                // Nếu đã "like", xóa "like" để thực hiện toggle (toggle off)
                if ($commentOwner->id != $user_id) {
                    $existedNotification = Notification::where('user_id', $commentOwner->id)
                    ->where('message', auth()->user()->username . " đã thích bình luận của bạn.")
                    ->where('manga_id', $comment->commentable_id)
                    ->where('comment_id', $comment->id)
                    ->first();

                    if ($existedNotification) {
                        $existedNotification->delete();
                    }
                }
                
                $likedComment->delete();
                return $this->response(['message' => 'Đã gỡ thích bình luận'], 200);
            } else {
                // Nếu chưa "like", xóa "dislike" (nếu có) và thêm "like"
                CommentReaction::where('user_id', $user_id)
                    ->where('comment_id', $comment_id)
                    ->where('type', 0) // "dislike"
                    ->delete();

                $like = CommentReaction::updateOrCreate(
                    [
                        'user_id' => $user_id,
                        'comment_id' => $comment_id
                    ],
                    ['type' => 1] // Đảm bảo kiểu là "like"
                );

                // Nếu comment không thuộc sở hữu của người đang "like", tạo thông báo
                if ($commentOwner->id != $user_id) {
                        Notification::create([
                            'user_id' => $commentOwner->id, // Người nhận thông báo
                            'message' => auth()->user()->username . " đã thích bình luận của bạn.",
                            'type' => 'like',
                            'manga_id' => $comment->commentable_id,
                            'comment_id' => $comment->id,
                            'related_user_id' => $user_id, // Người đã "like"
                        ]);
                }

                return $this->response(['message' => 'Đã thích bình luận', 'data' => $like], 200);
            }
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //dislike bình luận
    public function dislikeComment(Request $request)
    {
        try {
            $comment_id = intval($request->input('id'));
            $user_id = auth()->id();
            $comment = Comment::find($comment_id);
            $commentOwner = $comment->user;

            // Kiểm tra xem người dùng đã "dislike" bình luận này chưa
            $dislikedComment = CommentReaction::where('user_id', $user_id)
                ->where('comment_id', $comment_id)
                ->where('type', 0) // "dislike"
                ->first();

            if ($dislikedComment) {
                // Nếu đã "dislike", xóa để thực hiện toggle (toggle off)
                if ($commentOwner->id != $user_id) {
                    $existedNotification = Notification::where('user_id', $commentOwner->id)
                    ->where('message', auth()->user()->username . " đã không thích bình luận của bạn.")
                    ->where('manga_id', $comment->commentable_id)
                    ->where('comment_id', $comment->id)
                    ->first();

                    if ($existedNotification) {
                        $existedNotification->delete();
                    }
                }

                $dislikedComment->delete();
                return $this->response(['message' => 'Bạn đã hủy bỏ không thích bình luận'], 200);
            } else {
                // Nếu chưa "dislike", xóa "like" (nếu có) và thêm "dislike"
                CommentReaction::where('user_id', $user_id)
                    ->where('comment_id', $comment_id)
                    ->where('type', 1) // "like"
                    ->delete();

                $dislike = CommentReaction::updateOrCreate(
                    [
                        'user_id' => $user_id,
                        'comment_id' => $comment_id
                    ],
                    ['type' => 0] // Đảm bảo kiểu là "dislike"
                );

                // Nếu comment không thuộc sở hữu của người đang "dislike", tạo thông báo
                if ($commentOwner->id != $user_id) {
                    Notification::create([
                        'user_id' => $commentOwner->id, // Người nhận thông báo
                        'message' => auth()->user()->username . " đã không thích bình luận của bạn.",
                        'type' => 'like',
                        'manga_id' => $comment->commentable_id,
                        'comment_id' => $comment->id,
                        'related_user_id' => $user_id, // Người đã "like"
                    ]);
            }

                return $this->response(['message' => 'Bạn đã không thích bình luận này', 'data' => $dislike], 200);
            }
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //báo cáo bình luận
    // public function reportComment(Request $request){
    //     try {
    //     $comment_id = intval($request->input('id'));
    //     $manga_slug = $request->input('manga_slug');
    //     $manga = Manga::where('slug', $manga_slug)->first('id');
    //     if(!$manga){
    //         return response()->json(['message' => 'Truyện không tồn tại!'], 404);  // 404 Not Found
    //     }
    //     $reporter_id = auth()->id();
    //     $comment = Comment::find($comment_id);

    //     if(!$comment){
    //         return $this->response(['message' => 'Bình luận không tồn tại !'], 404);
    //     }

    //     $existingReport = CommentReport::where('comment_id', $comment_id)
    //         ->where('reporter_id', $reporter_id)
    //         ->first();

    //     if($existingReport){
    //         return $this->response(['message' => 'Bạn đã báo cáo bình luận này rồi!'], 200);
    //     }

    //     // $report_message = $request->input('content');
    //     $newCommentReport  = CommentReport::create([
    //         'comment_id' => $comment_id,
    //         'manga_id' => $manga->id,
    //         'reporter_id' => $reporter_id,
    //         'report_message' => ''
    //     ]);

    //     return $this->response(['message' => 'Báo cáo bình luận thành công', 'data' => $newCommentReport ], 200);
    // } catch (\Throwable $th) {
    //     return $this->response(['message' => $th->getMessage()], 500);
    // }
    // }

    public function reportComment(Request $request) {
        try {
            $comment_id = $request->input('id');
            $manga_slug = $request->input('manga_slug');
    
            $manga = Manga::where('slug', $manga_slug)->first();
            if (!$manga) {
                return $this->response(['message' => 'Truyện không tồn tại!'], 404);
            }
    
            $reporter_id = auth()->id();
            $comment = Comment::find($comment_id);
    
            if (!$comment) {
                return $this->response(['message' => 'Bình luận không tồn tại!'], 404);
            }
    
            $existingReport = CommentReport::where('comment_id', $comment_id)
                ->where('reporter_id', $reporter_id)
                ->first();
    
            if ($existingReport) {
                return $this->response(['message' => 'Bạn đã báo cáo bình luận này rồi!'], 200);
            }
    
            $newCommentReport = CommentReport::create([
                'comment_id' => $comment_id,
                // 'manga_id' => $manga->id,
                'reporter_id' => $reporter_id,
                'report_message' => '' 
            ]);
    
            return $this->response(['message' => 'Báo cáo bình luận thành công', 'data' => $newCommentReport], 200);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }
    

    //báo cáo lỗi
    public function reportChapter(Request $request)
    {
        $message = $request->message;
        $data['data'] = [
            'status' => 'success',
            'message' => $message
        ];
        return $this->response($data);
    }


    public function test(Request $request){
        try {
            //code...
        $image_url = $request->image;
        $imageUploader = new ImageStorageManager();
        $backblazePrefix = "/uploads/testing/manga/page_4_v11.webp";
        $client = new Client();
        $response = $client->request('GET', url($image_url));
        $status = $response->getStatusCode();
        if($status == 200){
        $imageData = $response->getBody()->getContents();
        $convertedToWebpImage = $imageUploader->convertToWebP($imageData);
        $uploadImageToBackblazeUrl = $imageUploader->uploadImageToBackblaze($backblazePrefix, $convertedToWebpImage);
        return $uploadImageToBackblazeUrl;
            }
    } catch (\Throwable $th) {
        return response()->json($th->getMessage(), 500);
    }
    }

    public function getImageUrlAfterByPass(Request $request) {
        $imageUrl = $request->image_url;
        $ch = curl_init();
        $referrer = config('custom.nettruyen_domain');

        curl_setopt($ch, CURLOPT_URL, $imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        // Thêm các header cần thiết
        $headers = array(
            "Referer: $referrer",
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Thực hiện truy vấn cURL, lấy nội dung ảnh
        $imageData = curl_exec($ch);

        // Kiểm tra lỗi và đóng cURL handle
        if(curl_errno($ch)) {
            curl_close($ch);
            return response()->json(['error' => 'Error fetching image']);
        }

        curl_close($ch);

        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        return response($imageData)->header('Content-Type', $contentType);
    }


    //lưu truyện
    public function saveManga(Request $request){
        try {
            $manga_slug = $request->input('manga_slug');
            $manga = Manga::where('slug', $manga_slug)->first(); 
            if(!$manga){
                return $this->response(['message' => 'Truyện không tồn tại!'], 404);  // 404 Not Found
            }
    
            $existingBookmark = Bookmark::where('bookmarkable_id', $manga->id)
                ->where('user_id', auth()->id())
                ->first();
    
            if ($existingBookmark) {
                $existingBookmark->delete();
                return $this->response(['message' => 'Bạn đã hủy lưu truyện.'], 200);
            }
    
            $bookmark = new Bookmark([
                'user_id' => auth()->id(),
                'bookmarkable_type' => Manga::class
            ]);
    
            $manga->bookmarks()->save($bookmark);
    
            return $this->response(['message' => 'Lưu truyện thành công !', 'data' => $bookmark], 200);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //bỏ lưu truyện
    // public function unsaveManga(Request $request){
    //     try {
    //         $manga_slug = $request->input('manga_slug');
    //         $manga = Manga::where('slug', $manga_slug)->first(); 
    //         if(!$manga){
    //             return response()->json(['message' => 'Truyện không tồn tại!'], 404);  // 404 Not Found
    //         }
    
    //         $existingBookmark = Bookmark::where('bookmarkable_id', $manga->id)
    //             ->where('user_id', auth()->id())
    //             ->first();
    
    //         if (!$existingBookmark) {
    //             return $this->response(['message' => 'Bạn chưa lưu truyện này.'], 500);
    //         }
    
    //         $existingBookmark->delete();
    
    //         return $this->response(['message' => 'Hủy lưu truyện thành công !'], 200);
    //     } catch (\Throwable $th) {
    //         return $this->response(['message' => $th->getMessage()], 500);
    //     }
    // }

    public function reportManga(Request $request){
        try {
        $manga_slug = $request->input('manga_slug');
        // $chapter_number = $request->input('chapter_number');
        $manga = Manga::where('slug', $manga_slug)->first('id');
        if(!$manga){
            return $this->response(['message' => 'Không tìm thấy truyện !'], 404);
        }

        // $chapter = $manga->chapters()->where('chapter_number', $chapter_number)->first('id');
        // if(!$chapter){
        //     return $this->response(['message' => 'Không tìm thấy chương của truyện !'], 404);
        // }

        $report = ChapterReport::create([
            'chapter_id' => $manga->id,
            'report_message' => $request->input('content')
        ]);
        return $this->response(['message'=> 'Báo cáo đã được gửi thành công!','data' => $report], 200);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //danh sách truyện đã lưu của người dùng
    public function myBookmarks(Request $request){
        try {
            $perPage = $request->input('per_page', 10);
            $bookmarks  = Bookmark::where('user_id', auth()->id())
            ->with(['manga' => function ($query) {
                $query->select(['id', 'title', 'slug', 'cover', 'created_at', 'updated_at']);
            }])
            ->paginate($perPage);

            $bookmarkedMangas = $bookmarks ->pluck('manga');

            $data = [
                "data" => [
                    "totalItems" => $bookmarks ->total(),
                    "items" => MangaResource::collection($bookmarkedMangas),
                    "pagination" => $this->parsePagination($bookmarks)
                    ]
                    ]; 
    
            return $this->response($data);

        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //danh sách bình luận của người dùng
    public function myComments(Request $request){
        try {
            $perPage = $request->input('per_page', 10);
            $comments = Comment::where('user_id', auth()->id())->orderByDesc('created_at')->paginate($perPage);

            $data = [
                "data" => [
                    "totalItems" => $comments->total(),
                    "items" => CommentResource::collection($comments),
                    "pagination" => $this->parsePagination($comments)
                    ]
                    ]; 
    
            // return $this->response(['data' => CommentResource::collection($comments)], 200);
            return $this->response($data);

        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    //danh sách thông báo của người dùng
    public function myNotifications(Request $request)
    {
        try {
        $perPage = $request->input('per_page', 10);
        
        // Lấy thời điểm người dùng đang truy cập web
        $currentTime = now();

        // $notifications = Notification::where('user_id', auth()->id())
        //     ->orderBy('created_at', 'desc')
        //     ->paginate($perPage);

        // Lấy danh sách truyện trong "bookmarks" của người dùng
        $bookmarks = auth()->user()->bookmarks()->where('bookmarkable_type', Manga::class)->pluck('bookmarkable_id');

        foreach ($bookmarks as $mangaId) {
            // Kiểm tra xem truyện có chapter mới không
            $newChapter = Chapter::where('manga_id', $mangaId)
                ->latest()
                ->first();

            if ($newChapter && $newChapter->created_at > $currentTime) {
                // Nếu chapter mới nhất được tạo sau thời điểm người dùng truy cập web, tạo thông báo
                $notification = new Notification([
                    'user_id' => auth()->id(),
                    'manga_id' => $mangaId,
                    'message' => 'Truyện ' . $newChapter->manga->title . ' đã có chapter mới: ' . $newChapter->title,
                ]);
                $notification->save();
            }
        }

        // Lấy 10 thông báo chưa đọc mới nhất
        $notifications = Notification::where('user_id', auth()->id())
        ->orderBy('status', 'asc')
        ->orderBy('created_at', 'desc') 
        ->paginate($perPage);

        $data = [
            "data" => [
                "totalItems" => $notifications->total(),
                "items" => NotificationResource::collection($notifications),
                "pagination" => $this->parsePagination($notifications)
                ]
                ];    

        return $this->response($data);

    } catch (\Throwable $th) {
        return $this->response(['message' => $th->getMessage()], 500);
    }
    }

    //đánh dấu đã xem thông báo
    public function readNotification(Request $request)
    {
        try {
        $notification_id = $request->id;

        $notification = Notification::find($notification_id);

        if ($notification) {
            $notification->status = true;
            $notification->save();

            return $this->response(['message' => 'Đã xem thông báo'], 200);
        }

        return $this->response(['message' => 'Không tìm thấy thông báo'], 404);
    } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }


    //REFRESH JWT TOKEN
    public function refreshToken(Request $request) {
            $refreshToken = $request->input('refresh_token');
            $storedToken = RefreshToken::where('token', $refreshToken)->first();
    
            if (!$refreshToken) {
                return response()->json([
                    'message' => 'Không có refresh_token được cung cấp.',
                ], 400);
            }

            if (!$storedToken) {
                return response()->json([
                    'message' => 'Refresh token không hợp lệ.',
                ], 401);
            }

            // Kiểm tra thời hạn
            if (Carbon::now()->greaterThan($storedToken->expires_at)) {
                return response()->json(['message' => 'Refresh token hết hạn rùi'], 401);
            }

            try {
            $newAccessToken = JWTAuth::fromUser($storedToken->user);

            $newRefreshToken = Str::random(40);
            $expiresAtCarbon = Carbon::parse($storedToken->expires_at);
            $expiresInSeconds = $expiresAtCarbon->diffInSeconds(Carbon::now());

            RefreshToken::updateOrCreate(
                ['user_id' => $storedToken->user->id], 
                [
                    'token' => $newRefreshToken,
                ]
            );
        
            // Trả về token mới
            return $this->response([
                'data' => [
                    'access_token' => $newAccessToken,
                    'expires_in' => $expiresInSeconds,
                    'refresh_token' => $newRefreshToken,
                    'token_type' => 'bearer',
                ]
            ], 200);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return $this->response([
            'message' => $e->getMessage(),
        ], 500);
        } catch (\Throwable $e) {
            return $this->response([
                'message' => $e->getMessage(),
            ], 500);
            // return response()->json([
            //     'message' => $e->getMessage(),
            // ], 500);
        }
    }

    //by pass ảnh truyenvn
    public function getTruyenVNByPassedImage(Request $request) {
    $imageUrl = $request->image_url;
    $referrer = 'https://truyenvn.me/';
    return ImageHelper::getImageUrlAfterByPass($imageUrl, $referrer);
    }

    //by pass ảnh truyenqqviet
    public function getTruyenQQVietByPassedImage(Request $request) {
        $imageUrl = $request->image_url;
        $referrer = 'https://truyenqqviet.com/';
        return ImageHelper::getImageUrlAfterByPass($imageUrl, $referrer);
    }
    
     public function getBPImage(){
        $referrer = request()->referrer;
        $imageUrl = request()->image_url;
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Referer: ' . $referrer,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]);

            $imageData = curl_exec($ch);
            curl_close($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            
            return response($imageData)->header('Content-Type', $contentType);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    
}
