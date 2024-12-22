<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\APIs\Contracts\ApiBase ;
use App\Http\Resources\MangaResource;
use App\Http\Resources\SeoResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Setting;
use Ophim\Core\Models\Taxonomy;

class MangaController extends ApiBase
{

    protected $columnsToSelect;
    protected $seoData = [];

    public function __construct()
    {
        $this->columnsToSelect = ['id', 'title', 'slug', 'alternative_titles', 'cover', 'description', 'created_at', 'updated_at'];
    }

    protected function getSeoResource($seoData)
    {
        $seoData['titleHead'] = $this->seoData['titleHead'] ?? null;
        $seoData['descriptionHead'] = $this->seoData['descriptionHead'] ?? null;
        $seoData['keywordsHead'] = $this->seoData['keywordsHead'] ?? null;
        $seoData['shortcutIconHead'] = $this->seoData['shortcutIconHead'] ?? null;
        $seoData['tagsHead'] = $this->seoData['tagsHead'] ?? null;
       
        return new SeoResource(null, $seoData);
    }

    protected function getNotifications(){
        return Setting::where('key', 'notifications')->value('value');
    }


    /**
     * @OA\Get(
     *     path="/api/v1/home",
     *     summary="Trang chủ",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    // public function index()
    // {   
    //     try {
    //             $data = [
    //                 "data" => [
    //                     'seoOnPage' => $this->getSeoResource($this->seoData),
    //                     "notication_messages" => $this->getNotifications(),
    //                     "slider_mangas" => $this->getSliderMangas() ?? $this->getFallbackSliderMangas(),
    //                     "popular_day_mangas" => $this->getPopularMangas('day') ?? [],
    //                     "popular_week_mangas" => $this->getPopularMangas('week') ?? [],
    //                     "popular_month_mangas" => $this->getPopularMangas('month') ?? [],
    //                     "latest_updates" => $this->getLatestUpdates(),
    //                     "latest_mangas" => $this->getLatestMangas(),
    //                 ]
    //             ];

    //         return $this->response($data);
    //     } catch (\Throwable $th) {
    //         // return $this->response(["message" => $th->getMessage()], 500);
    //         return response()->json(['msg' => $th->getMessage()], 500);
    //     }
    // }

    public function index()
    {   
        try {
            $cacheKey = 'home_page_data';
            $cacheExpiration = now()->addMinutes(5);

            $data = Cache::remember($cacheKey, $cacheExpiration, function () {
                return [
                    "data" => [
                        'seoOnPage' => $this->getSeoResource($this->seoData),
                        "notication_messages" => $this->getNotifications(),
                        "slider_mangas" => $this->getSliderMangas() ?? $this->getFallbackSliderMangas(),
                        "popular_day_mangas" => $this->getPopularMangas('day') ?? [],
                        "popular_week_mangas" => $this->getPopularMangas('week') ?? [],
                        "popular_month_mangas" => $this->getPopularMangas('month') ?? [],
                        "latest_updates" => $this->getLatestUpdates(),
                        "latest_mangas" => $this->getLatestMangas(),
                    ]
                ];
            });

            return $this->response($data);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()], 500);
        }
    }

    // protected function filterMangasQuery($mangaQuery, $request){
    //     $type = $request->input("type");
    //         if ($type) {
    //             $mangaQuery->whereHas("types", function ($query) use ($type) {
    //                 $query->where("name", "like", "%{$type}%")
    //                 ->orWhere("slug", $type);
    //             });
    //         }

    //         $status = $request->input("status");
    //         if ($status) {
    //             $mangaQuery->whereHas("statuses", function ($query) use ($status) {
    //                 $query->where("name", "like", "%{$status}%")
    //                 ->orWhere("slug", $status);
    //             });
    //         }

    //         $category = $request->input("category");
    //         if ($category) {
    //             $mangaQuery->whereHas("genres", function ($query) use ($category) {
    //                 $query->where("name", "like", "%{$category}%")
    //                 ->orWhere("slug", $category);
    //             });
    //         }

    //         return $mangaQuery;
    // }

    protected function filterMangasQuery($mangaQuery, $request)
    {
        $filters = ['type' => 'types', 'status' => 'statuses', 'category' => 'genres'];

        foreach ($filters as $param => $relation) {
            $value = $request->input($param);
            if ($value) {
                $mangaQuery->whereHas($relation, function ($query) use ($value) {
                    $query->where(function ($q) use ($value) {
                        $q->where("name", "like", "%{$value}%")
                        ->orWhere("slug", $value);
                    });
                });
            }
        }

        return $mangaQuery;
    }

    public function getSliderMangas()
    {
        $cacheKey = 'slider_mangas';

        // Attempt to retrieve data from cache
        $sliderMangas = Cache::remember($cacheKey, 480 * 60, function () {
            return Manga::orderByDesc('created_at')
                        ->where('is_recommended', true)
                        ->limit(15)
                        ->get($this->columnsToSelect);
        });

        if ($sliderMangas->isEmpty()) {
            return null;
        }

        return MangaResource::collection($sliderMangas);
    }

    protected function getFallbackSliderMangas(){
        $cacheKey = 'fallback_mangas';
        $cacheExpiration = now()->addHours(5);

        $fallbackMangas = Cache::remember($cacheKey, $cacheExpiration, function () {
            return Manga::orderByDesc('created_at')->limit(14)->get($this->columnsToSelect);
        });
        return MangaResource::collection($fallbackMangas);
    }

    public function getPopularMangas($timeType)
    {
        $timeRanges = [
            'day' => 'startOfDay',
            'week' => 'startOfWeek',
            'month' => 'startOfMonth'
        ];

        return Cache::remember("popular_mangas_{$timeType}", now()->addHours(8), function () use ($timeType, $timeRanges) {
            $startDate = Carbon::now()->{$timeRanges[$timeType] ?? 'startOfDay'}();
            
            return Manga::select(
                'mangas.title', 
                'mangas.slug', 
                'mangas.cover',
                DB::raw('SUM(views.views) as total_views')
            )
            ->join('views', 'mangas.id', '=', 'views.key')
            ->where('views.updated_at', '>=', $startDate)
            ->groupBy('mangas.id', 'mangas.title', 'mangas.slug', 'mangas.cover')
            ->orderByDesc('total_views')
            ->limit(10)
            ->get();
        });
    }

    public function getLatestUpdates()
{
    $columnsToSelect = ['id', 'title', 'slug', 'cover'];
    $mangas = Manga::select($columnsToSelect)->whereHas('chapters')->orderBy('updated_at', 'desc')->limit(12)->get();
    return MangaResource::collection($mangas);
}


    public function getLatestMangas()
    {
            $columnsToSelect = ['title', 'slug', 'cover'];
            $latestManga = Manga::whereHas('chapters')->orderByDesc('created_at')->limit(14)->get($columnsToSelect);
            return MangaResource::collection($latestManga);
    }

    
    //các truyện liên quan (nằm trong chi tiết truyện)
    public function getRelativeMangas($genres){
        $genreSlugs = $genres->pluck('slug')->toArray();
        $mangas = Manga::whereHas('genres', function ($query) use ($genreSlugs) {
         $query->whereIn('slug', $genreSlugs);
     })->inRandomOrder()->limit(10)->get($this->columnsToSelect);
        return MangaResource::collection($mangas);
     }

    

    /**
     * @OA\Get(
     *     path="/api/v1/tim-kiem",
     *     summary="Tìm kiếm",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    //tim kiem
    public function search(){
        try {
            $perPage = 28;
            $currentPage = request()->get("page", 1);
            $keyword = request()->input('keyword');
            $words = explode(' ', $keyword);
            $columnsToSelect = ['title', 'slug', 'description', 'cover', 'created_at', 'updated_at'];
            $mangas = Manga::where(function ($query) use ($keyword, $words) {
                $query->orWhere(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('title', 'LIKE', $keyword . '%')
                        ->orwhere("author", "like", "%" . $keyword . "%")
                        ->orWhere('alternative_titles', 'LIKE', $keyword . '%')
                        ->orwhere("description", "like", "%" . $keyword . "%")
                        ->orWhere('slug', 'LIKE', $keyword . '%');
                });

                foreach ($words as $word) {
                    $query->orWhere('title', 'LIKE', '%' . $word . '%');
                }
            })
            ->orderBy(function ($query) use ($keyword) {
                return $query->selectRaw("CASE WHEN title LIKE '{$keyword}%' THEN 0 ELSE 1 END");
            })->select($columnsToSelect);

            $mangas = $this->filterMangasQuery($mangas, request())->paginate($perPage);

            $this->seoData['og_url'] = "https://10truyen.com/tim-kiem?keyword=$keyword";
            $settings = Setting::whereIn('key', [
                'site_tag_key',
            ])->get()->keyBy('key');
            $this->seoData['titleHead'] = "Tìm truyện tranh {$keyword} online - 10Truyen";
            $descriptionHeadTemp = $settings->get('site_tag_key')->value;
            $descriptionHead = str_replace(
                ['{name}'],
                [$keyword ?? ''],
                $descriptionHeadTemp
            );
            $this->seoData['descriptionHead'] = $descriptionHead;

            $data = [
                "data" => [
                    'seoOnPage' => $this->getSeoResource($this->seoData),
                    "breadcrumbs" =>[
                        [
                        'name' => "Tìm kiếm truyện: $keyword - Trang $currentPage",
                        "isCurrent" => true,
                        "position" => 2,
                        ]
                    ],
                    "titlePage" => "Tìm kiếm truyện: $keyword",
                    "items" => MangaResource::collection($mangas),
                    "pagination" => $this->parsePagination($mangas),
                ]
                ];
            return $this->response($data);

        } catch (\Throwable $th) {
            return $this->response(["message" => $th->getMessage()], 500);
            // return $th->getMessage();
        }
    }


    public function getMangasByCategory(Request $request){
            try {
            $categorie = $request->categorie;
            $category = Taxonomy::where('slug', $categorie)->first(['name', 'slug', 'seo_title', 'seo_des']);
            $resultQuery = Manga::whereHas("genres", function ($query) use($categorie) {
                $query->where('slug', $categorie);
            });

            $mangas = $this->filterMangasQuery($resultQuery, request())->select('title','slug', 'cover')->paginate(28);

            $this->seoData['titleHead'] = $category->seo_title;
            $this->seoData['descriptionHead'] = $category->seo_des;
            $currentYear = Carbon::now()->year;
            $keywordsHead = "{$category->name} mới nhất, {$category->name} {$currentYear}, {$category->name} hay nhất";
            $this->seoData['keywordsHead'] = $keywordsHead;
            $this->seoData['og_url'] = "https://10truyen.com/the-loai/" . $category->slug;

            $data = [
                    'seoOnPage' => $this->getSeoResource($this->seoData),
                    "items" => MangaResource::collection($mangas),
                    "pagination" => $this->parsePagination($mangas),
                ];

            return $this->response(["data" => $data]);
        } catch (\Throwable $th) {
           return response()->json($th->getMessage(), 500);
        }
    }

    public function getMangasByListType(Request $request)
    {
        try {
            $perPage = 28;
            $categorie = $request->categorie;

            $data = [];
            switch ($categorie) {
                case 'moi-cap-nhat':
                    $latestUpdateMangas = Manga::orderByDesc('updated_at');
                    $latestUpdateMangas = $this->filterMangasQuery($latestUpdateMangas, request())->select('title','slug', 'cover')->paginate($perPage);
                    $this->seoData['og_url'] = 'https://10truyen.com/moi-cap-nhat';
                    $data['seoData'] = $this->getSeoResource($this->seoData);
                    $data['items'] = MangaResource::collection($latestUpdateMangas);
                    $data['pagination']= $this->parsePagination($latestUpdateMangas);
                    break;
                case 'truyen-moi':
                    $latestMangas = Manga::orderByDesc('created_at')->select($this->columnsToSelect);
                    $latestMangas = $this->filterMangasQuery($latestMangas, request())->select('title','slug', 'cover')->paginate($perPage);
                    $this->seoData['og_url'] = 'https://10truyen.com/truyen-moi';
                    $data['seoData'] = $this->getSeoResource($this->seoData);
                    $data['items'] = MangaResource::collection($latestMangas);
                    $data['pagination']= $this->parsePagination($latestMangas);
                    break;
                default:
                    $data = $this->getMangasByCategory($request);
            }

        return $this->response(["data"=>$data]);
    } catch (\Throwable $th) {
        return response()->json($th->getMessage(), 500);
    }
    }

    public function getRandomManga()
    {
    try {
        $randomManga = Manga::inRandomOrder()->first('slug');
        return $this->response(["data"=>$randomManga]);
    } catch (\Throwable $th) {
        return $this->response($th->getMessage(), 500);
    }
    }

    public function getScheduledMangas(Request $request){
     try {
       $perPage = 28;
       $weekday = $request->weekday;
       $weekdays = [
        'hang-ngay' => 0,
        'chu-nhat' => 1,
        'thu-hai' => 2,
        'thu-ba' => 3,
        'thu-tu' => 4,
        'thu-nam' => 5,
        'thu-sau' => 6,
        'thu-bay' => 7,
    ];
    if (!isset($weekdays[$weekday])) {
        return response()->json(['error' => 'Tham số không hợp lệ'], 500);
    }

      $mangas = Manga::where('is_shown_in_weekly', true)->where('showntimes_in_weekday', $weekdays[$weekday])->select($this->columnsToSelect)->paginate($perPage);
      $data = [
        'seoOnPage' => $this->getSeoResource($this->seoData),
        "items" => MangaResource::collection($mangas),
        "pagination" => $this->parsePagination($mangas),
        ];

        return $this->response(["data"=>$data]);
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
    }

    

}
