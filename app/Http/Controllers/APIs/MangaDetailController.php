<?php

namespace App\Http\Controllers\APIs;

use App\Helpers\DateHelper;
use App\Http\Controllers\APIs\Contracts\ApiBase ;
use App\Http\Resources\CommentResource;
use App\Http\Resources\MangaDetailResource;
use App\Http\Resources\MangaResource;
use App\Http\Resources\SeoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Setting;

class MangaDetailController extends ApiBase
{

    

    protected $columnsToSelect;
    protected $seoData = [];

    public function __construct()
    {
        $this->columnsToSelect = ['id', 'title', 'slug', 'alternative_titles', 'cover', 'description', 'created_at', 'updated_at'];
    }

    protected function getSeoResource($manga, $seoData, $chapter_number = null)
    {
        $settings = Setting::whereIn('key', [
            'site_meta_shortcut_icon',
            'site_movie_title',
            'site_tag_title',
            'site_tag_key',
        ])->get()->keyBy('key');

        $titleHeadTemp = $settings->get('site_movie_title')->value;
        $titleHead = str_replace(
            ['{name}', '{origin_name}'],
            [$manga->title ?? '', $manga->alternative_titles ?? ''],
            $titleHeadTemp
        );
        
        $seoData['titleHead'] = $titleHead;
        
        $seoData['seoSchema']["name"] = $manga->title ?? '';
        $seoData['seoSchema']["url"] = "https://10truyen.com/truyen/" . $manga->slug;
        $seoData['seoSchema']["image"] = $manga->cover;
        $seoData['descriptionHead'] = $manga->description ;
        $keywordsHeadTemp = $settings->get('site_tag_key')->value;
        $keywordsHead = str_replace(
            ['{name}'],
            [$manga->title ?? ''],
            $keywordsHeadTemp
        );
        $seoData['keywordsHead'] = $keywordsHead;
        $seoData['shortcutIconHead'] = asset($settings->get('site_meta_shortcut_icon')->value);
        $seoData['og_url'] = "https://10truyen.com/truyen/$manga->slug";

        if ($chapter_number) {
            $seoData['seoSchema']["name"] .= " - Chapter {$chapter_number}";
            $seoData['seoSchema']["url"] .= "/{$chapter_number}";
            $seoData['titleHead'] = str_replace(
                ['{name}', '{origin_name}'],
                ["{$manga->title} - Chapter $chapter_number" ?? '', $manga->alternative_titles ?? ''],
                $titleHeadTemp
            );;
            $seoData['og_url'] .= "/{$chapter_number}";
        }

        return new SeoResource(null, $seoData);
    }

    protected function getNotifications(){
        return Setting::where('key', 'notifications')->value('value');
    }

    protected function getSeoResourceOfMangaDetail($manga, $seoData)
    {
        $settings = Setting::whereIn('key', [
            'site_movie_title',
            'site_tag_key',
        ])->get()->keyBy('key');

        $latestChapters = $manga->lastestChapter()->first(['chapter_number']);

        $titleHeadTemp = $settings->get('site_movie_title')->value;
        $titleHead = str_replace(
            ['{name}', '{origin_name}', '{episode_chapter}'],
            [$manga->title ?? '', '- ' . $manga->alternative_titles, $latestChapters->chapter_number ?? 'mới nhất'],
            $titleHeadTemp
        );  
        $seoData['titleHead'] = preg_replace('/\s+/', ' ', trim($titleHead));
        
        $seoData['seoSchema']["url"] = "https://10truyen.com/truyen/" . $manga->slug;
        $seoData['seoSchema']["image"] = $manga->cover;
        $seoData['seoSchema']["director"] = $manga->author;
        $seoData['og_image'] = $manga->cover;
        $seoData['descriptionHead'] = $manga->description;
        $keywordsHeadTemp = $settings->get('site_tag_key')->value;
        $keywordsHead = str_replace(
            ['{name}'],
            [$manga->title ?? ''],
            $keywordsHeadTemp
        );
        $seoData['keywordsHead'] = $keywordsHead;
        $seoData['og_url'] = "https://10truyen.com/truyen/$manga->slug";

        return new SeoResource(null, $seoData);
    }

    public function getMangaDetail($slug)
{   
    try {
        $cacheExpiration = now()->addHours(3);

        $manga = Cache::remember('manga_detail_'.$slug, $cacheExpiration, function () use ($slug) {
            return Manga::where('slug', $slug)->first();
            // return Manga::with('genres', 'types', 'statuses', 'chapters', 'views')->where('slug', $slug)->first();
        });

        if (!$manga) {
            return $this->response(["message" => 'Không tồn tại truyện !'], 404); 
        }

        //tính lượt xem
        $manga->addView();

        //danh sách truyện mới nhất
        $latestMangas = Cache::remember('latest_mangas', 3600, function () {
            return Manga::take(10)->get(['title', 'slug', 'cover']);
        });

        //danh sách truyện liên quan
        $relatedMangas = $manga->relativeMangas()->isNotEmpty() ? $manga->relativeMangas() : $latestMangas;

        $data = [
            "data" => [
                'seoOnPage' => $this->getSeoResourceOfMangaDetail($manga, $this->seoData),
                "breadcrumbs" => $manga->genres->map(function ($genre) {
                    return [
                        'name' => $genre->name,
                        'slug' => "https://10truyen.com/the-loai/" . $genre->slug,
                        'position' => 2 
                    ];
                })->push([
                    "name" => $manga->title,
                    "isCurrent" => true,
                    "position" => 3,
                ])->all(),
                "params" => [
                    "slug" => $manga->slug
                ],
                "item" => new MangaDetailResource($manga),
                'related' => MangaResource::collection($relatedMangas, 'slider'),
                'lastest_manga' => MangaResource::collection($latestMangas)
            ],
        ];

        return $this->response($data);
    } catch (\Throwable $th) {
        // return $this->response(["message" => $th->getMessage()], 500);
        return $th->getMessage();
    }
}

    public function getComments(Request $request, $slug)
    {   
        $perPage = 10;
        $sortType = $request->input('type', 'moi-nhat');
        try {
            $manga = Manga::with('comments')->where('slug', $slug)->first(['id']);

            if (!$manga) {
                return $this->response(["message" => 'Không tồn tại truyện !'], 404); 
            }

            // Xác định thứ tự sắp xếp dựa trên `sortType`
            if ($sortType === 'cu-nhat') {
                $orderBy = 'asc'; // Sắp xếp từ cũ đến mới
            } else {
                $orderBy = 'desc'; // Sắp xếp từ mới đến cũ
            }

            $comments = $manga->comments()->orderBy('created_at', $orderBy)->paginate($perPage);

            $data = [
                "data" => [
                    "totalComments" => $comments->total(),
                    "item" => CommentResource::collection($comments),
                    "pagination" => $this->parsePagination($comments)
                ],
            ];

            return $this->response($data);
        } catch (\Throwable $th) {
            return $this->response(["message" => $th->getMessage()], 500);
        }
    }

    protected function getSeoResourceOfMangaChapters($manga, $seoData, $chapter_number)
    {
        $settings = Setting::whereIn('key', [
            'site_episode_watch_title',
            'site_tag_key',
        ])->get()->keyBy('key');

        $titleHeadTemp = $settings->get('site_episode_watch_title')->value;
        $titleHead = str_replace(
            ['{manga.name}', '{manga.origin_name}', '{manga.chapter_current}'],
            [$manga->title, ('- ' . $manga->alternative_titles), $chapter_number ?? 'mới nhất'],
            $titleHeadTemp
        );  
        $seoData['titleHead'] = preg_replace('/\s+/', ' ', trim($titleHead));
        $seoData['og_image'] = $manga->cover;
        
        $seoData['seoSchema']["url"] = "https://10truyen.com/doc-truyen/$manga->slug/$chapter_number";
        $seoData['seoSchema']["image"] = $manga->cover;
        $seoData['descriptionHead'] = $manga->description;
        $keywordsHeadTemp = $settings->get('site_tag_key')->value;
        $keywordsHead = str_replace(
            ['{name}'],
            [$manga->title . " tập $chapter_number" ?? ''],
            $keywordsHeadTemp
        );
        $seoData['keywordsHead'] = $keywordsHead;
        $seoData['og_url'] = "https://10truyen.com/doc-truyen/$manga->slug/$chapter_number";

        return new SeoResource(null, $seoData);
    }

    public function getChaptersOfManga(Request $request){
        try {
            $manga_slug = $request->manga_slug;
            $manga = Cache::rememberForever("manga_{$manga_slug}", function () use ($manga_slug) {
                return Manga::where('slug', $manga_slug)
                    ->firstOrFail(['id', 'title', 'description', 'alternative_titles', 'cover', 'slug']);
            });
            
            $chapter_number = $request->chapter;
            $chapter = $manga->chapters()
                    ->where('chapter_number', $chapter_number)
                    ->first();
            if(!$chapter){
                return $this->response(["message" => 'Không tồn tại chapter !'], 404); 
            }

            //tính lượt xem
            $manga->addView();

            $chapters = $manga->chapters()
            ->whereBetween("chapter_number", [1, $chapter_number + 1]) 
            ->orderBy("chapter_number")
            ->get(['chapter_number']);

            $previousChapter = $chapters->where("chapter_number", "<", $chapter_number)->last();
            $nextChapter = $chapters->where("chapter_number", ">", $chapter_number)->first(); 

            $options = $manga->chapters()
            ->get(["chapter_number as number"]) 
            ->sortByDesc('number') 
            ->unique('number') 
            ->values();  

            // $chapterImages = [
            //     'servers' => [
            //         [
            //             'server_name' => 'Server 1',
            //             'images' => collect($chapter->content)->map(function ($image, $key) {
            //                 return [
            //                     'page' => $key + 1,
            //                     'file' => $image,
            //                 ];
            //             }),
            //         ],
            //         [
            //             'server_name' => 'Server 2',
            //             'images' => collect($chapter->content_sv2)->map(function ($image, $key) {
            //                 return [
            //                     'page' => $key + 1,
            //                     'file' => $image,
            //                 ];
            //             }),
            //         ],
            //     ],
            // ];

            $chapterImages = collect($chapter->status === 'uploaded_to_storage' ? $chapter->content_sv2 : $chapter->content)
            ->map(function ($image, $key) {
                return [
                    "image_page" => $key + 1,
                    "image_file" => $image,
                ];
            });

            $data = [
                "data" => [
                    'seoOnPage' => $this->getSeoResourceOfMangaChapters($manga, $this->seoData, $chapter_number),
                    // "breadcrumbs" => $manga->genres->map(function ($genre) {
                    //     return [
                    //         'name' => $genre->name,
                    //         'slug' => "https://10truyen.com/the-loai/" . $genre->slug,
                    //         'position' => 2 
                    //     ];
                    // })->push([
                    //     "name" => $manga->title,
                    //     "slug" => "https://10truyen.com/truyen/" . $manga->slug,
                    //     "position" => 3,
                    // ], 
                    // [
                    //     "name" => floatval($chapter_number),
                    //     "isCurrent" => true,
                    //     "position" => 4,
                    // ])->all(),
                    // "params" => [
                    //     "slug" => $manga->slug,
                    //     "chapter" => $chapter_number
                    // ],
                    "item" => [
                        "comic_name" => $manga->title,
                        // "other_name" => explode(",", $manga->alternative_titles),
                        'previous_chapter' => $previousChapter ? $previousChapter->chapter_number : "",
                        "next_chapter" => $nextChapter ? $nextChapter->chapter_number : "",
                        "chapter_number" => $chapter->chapter_number,
                        "slug" => $manga->slug,
                        "chapter_name" => $chapter->title,
                        "chapter_path" => '',
                        "chapter_image" => $chapterImages,
                    ],
                    "chapter" => $options
                ],
                ];
            return $this->response($data);
        } catch (\Throwable $th) {
            return $this->response(["message" => $th->getMessage()], 500);
        }
    }


    public function getRatingInfo(Request $request){
        try {
            //code...
        $manga_slug = $request->slug;
        // $manga = Manga::where('slug', $manga_slug)->first();
        $manga = Manga::where('slug', $manga_slug)->first(['id']);
        if(!$manga){
            return $this->response(['message' => 'Không tìm thấy truyện'], 404);
        }

        $user = auth()->user();
        $userRating = null;
        $userIconRating = null;

        if ($user) {
            // Lấy đánh giá của người dùng đối với manga
            $userRating = $user->rating()->where('manga_id', $manga->id)->first();

            // Lấy đánh giá bằng biểu tượng của người dùng đối với manga
            $userIconRating = $user->ratingIcon()->where('manga_id', $manga->id)->first();
        }

        // Xử lý an toàn trường hợp `userRating` và `userIconRating` là null
        $userRatingValue = $userRating->rating ?? 0;
        $userIconRatingValue = $userIconRating->icon ?? 'none';

        $totalRatings = $manga->starRatings()->count();
        $totalRatingIcons = $manga->iconRatings()->count();

        // Xây dựng dữ liệu trả về
        $data = [
            'rating' => $userRatingValue,
            'ratings' => $totalRatings,
            'iconRating' => $userIconRatingValue,
            'iconRatings' => $totalRatingIcons,
        ];

        // Trả về phản hồi với dữ liệu
        return $this->response(['data'=> $data]);

    } catch (\Throwable $th) {
        return $this->response(['message' => $th->getMessage()], 500);
    }

    }

    public function isMangaSaved($slug)
    {
        try {
        $manga = Manga::where('slug', $slug)->first(['id']);

        if (!$manga) {
            return response()->json(['message' => 'Truyện không tồn tại'], 404);
        }

        // Mặc định "saved" là false
        $isSaved = false;

        if ($user = auth()->user()) {
            $isSaved = $user->bookmarks()->where('bookmarkable_id', $manga->id)->exists();
        }

        $data = ['saved' => $isSaved];

        return $this->response(['data' => $data], 200);
    } catch (\Throwable $th) {
        return $this->response(['message' => $th->getMessage()], 500);
    }
    }




}
