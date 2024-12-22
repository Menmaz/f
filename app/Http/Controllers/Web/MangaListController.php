<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\MangaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;

class MangaListController extends Controller
{
    protected $perPage = 21;

    protected function baseMangaQuery() {
        return Manga::select(['id', 'title', 'slug', 'cover'])
            ->with([
                'taxanomies' => function ($query) {
                    return $query->select('name', 'slug', 'type');
                },
                'chapters' => function ($query) {
                    $query
                        ->orderBy('chapter_number', 'desc')
                        ->select('manga_id', 'chapter_number', 'created_at')
                        ->limit(3);
                }
            ])
            ->withAvg('starRatings', 'rating')
            ->withCount('starRatings')
            ->withSum('views', 'views');
    }

    protected function getMangaList($typeList, $category_slug = null){
        $query = $this->baseMangaQuery();

        switch ($typeList) {
            case 'truyen-moi':
                $query->orderBy('created_at', 'desc');
                break;
            case 'moi-cap-nhat':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'the-loai':
                $query->whereHas('taxanomies', function ($query) use ($category_slug) {
                    $query->where('slug', $category_slug);
                });
                break;
        }

       return $query->paginate($this->perPage);
    }

    protected function getSeoData($title = null, $category = null)
    {
        $settings = $this->getSeoSettings();
        return [
            'title' => $title ?? $settings->get('site_meta_siteName')->value,
            'description_meta' => $category->seo_des ?? $settings->get('site_meta_description')->value,
            'keywords_meta' => $settings->get('site_meta_keywords')->value,
            'image_meta' => $settings->get('site_meta_image')->value,
            'head_tags_meta' => $settings->get('site_meta_head_tags')->value,
            'site_script' => $settings->get('site_scripts_google_analytics')->value,
        ];
    }

    public function showMangasByCategory($category_slug)
    {       
        $categories = $this->getCategories();
        $category = $categories->where('slug', $category_slug)->first();   
        $seoData = $this->getSeoData($category->seo_title, $category);
        $seoData['title'] = $category->seo_title;
        $seoData['description_meta'] = $category->seo_des;
        $seoData['keywords_meta'] = $category->key;

        $data = [
            'seoData' => $seoData,
            'title' => 'Thể loại ' . $category->name,
            'sort_by' => 'recently_updated',
            'category' => $category,
            'categories' => $categories,
            'statuses' => $this->getStatus(),
            'updatedMangas' => $this->getMangaList('the-loai', $category_slug)
           ];

           return view('frontend-web.manga-list.index', $data);
    }


    public function showLatestMangas()
    {   
        $seoData = $this->getSeoData();

        $data = [
            'title' => 'Truyện mới nhất',
            'seoData' => $seoData,
            'sort_by' => 'recently_added',
            'categories' => $this->getCategories(),
            'statuses' => $this->getStatus(),
            'updatedMangas' => $this->getMangaList('truyen-moi')
           ];

           return view('frontend-web.manga-list.index', $data);
    }

    public function showLatestUpdatedMangas()
    {           
        $seoData = $this->getSeoData();

        $data = [
            'title' => 'Truyện mới cập nhật',
            'seoData' => $seoData,
            'sort_by' => 'recently_updated',
            'categories' => $this->getCategories(),
            'statuses' => $this->getStatus(),
            'updatedMangas' => $this->getMangaList('moi-cap-nhat')
           ];

           return view('frontend-web.manga-list.index', $data);
    }

    private function parseArrayParameter($parameter)
    {
        if (is_string($parameter)) {
            return explode(',', $parameter);
        }
        return $parameter;
    }

    public function filter(Request $request){
        try {
        $keyword = $request->input('keyword');
        $types = $this->parseArrayParameter($request->input('type', []));
        $genres = $this->parseArrayParameter($request->input('genre', []));
        $genre_mode = $request->input('genre_mode');
        $statuses = $this->parseArrayParameter($request->input('status', []));
        $years = $this->parseArrayParameter($request->input('year', []));
        $sort = $request->input('sort');

        $query = $this->baseMangaQuery();

        if($keyword){
            $query->where('title', 'LIKE', '%' . $keyword . '%');
        }

        if (!empty($types) && is_array($types)) {
            foreach ($types as $type) {
                $query->whereHas('taxanomies', function ($subQuery) use ($type) {
                    $subQuery->where('slug', $type);
                });
            }
        }

        //lọc theo thể loại
        if (!empty($genres) && is_array($genres)) {
            foreach ($genres as $genre) {
                $query->whereHas('taxanomies', function ($subQuery) use ($genre) {
                    $subQuery->where('slug', $genre);
                });
            }
            // chế độ này bắt buộc truyện phải có đủ thể loại đã chọn 
            if($genre_mode){
                $query->whereHas('taxanomies', function ($subQuery) use ($genres) {
                    $subQuery->whereIn('slug', $genres);
                }, '=', count($genres));
            }
        }
        
        if (!empty($statuses) && is_array($statuses)) {
            $query->whereHas('taxanomies', function ($subQuery) use ($statuses) {
                $subQuery->whereIn('slug', $statuses);
            });
        }

        if (!empty($years) && is_array($years)) {
            $query->whereIn(DB::raw('YEAR(created_at)'), $years);
        }
    
        // Sorting
        if ($sort) {
            if($sort == 'moi-cap-nhat'){
                $query->orderBy('updated_at', 'desc');
            } else if($sort == 'truyen-moi'){
                $query->orderBy('created_at', 'desc');
            } else if($sort == 'a-z'){
                $query->orderBy('title', 'asc');
            } else if($sort == 'danh-gia-sao'){
                $query->withAvg('starRatings', 'rating')
                ->orderByDesc('star_ratings_avg_rating'); 
            } else if($sort == 'luot-xem'){
                $query->withSum('views', 'views')
                ->orderByDesc('views_sum_views'); 
            }
        } else {
            $query->orderBy('updated_at', 'desc');
        }
    
        $mangas = $query->paginate($this->perPage);

        $settings = $this->getSeoSettings();
        $seoData['title'] = $settings->get('site_meta_siteName')->value;
        $seoData['icon'] = asset($settings->get('site_meta_shortcut_icon')->value);
        $seoData['description_meta'] = $settings->get('site_meta_description')->value;
        $seoData['keywords_meta'] = $settings->get('site_meta_keywords')->value;
        $seoData['image_meta'] = $settings->get('site_meta_image')->value;
        $seoData['head_tags_meta'] = $settings->get('site_meta_head_tags')->value;
        $seoData['site_script'] = $settings->get('site_scripts_google_analytics')->value;

        $data = [
            'title' => 'Lọc',
            'seoData' => $seoData,
            'sort_by' => $sort ?? 'moi-cap-nhat',
            'categories' => $this->getCategories(),
            'types_selected' => $types,
            'genres_selected' => $genres,
            'genre_mode' => $genre_mode,
            'statuses_selected' => $statuses,
            'years_selected' => $years,
            'statuses' => $this->getStatus(),
            'updatedMangas' => $mangas
           ];

           return view('frontend-web.manga-list.index', $data);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage());
        }
    }

    public function getScheduledMangas(Request $request){
        try {
          $weekday = $request->input('weekday');
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
           $weekday = 'hang-ngay';
       }

         $mangas = $this->baseMangaQuery()->where('showntimes_in_weekday', $weekdays[$weekday])
         ->paginate($this->perPage);

        $settings = $this->getSeoSettings();
        $seoData['title'] = $settings->get('site_meta_siteName')->value;
        $seoData['icon'] = asset($settings->get('site_meta_shortcut_icon')->value);
        $seoData['description_meta'] = $settings->get('site_meta_description')->value;
        $seoData['keywords_meta'] = $settings->get('site_meta_keywords')->value;
        $seoData['image_meta'] = $settings->get('site_meta_image')->value;
        $seoData['head_tags_meta'] = $settings->get('site_meta_head_tags')->value;
        $seoData['site_script'] = $settings->get('site_scripts_google_analytics')->value;

         $data = [
           'title' => 'Lịch truyện',
           'seoData' => $seoData,
           "updatedMangas" => $mangas,
           ];
   
           return view('frontend-web.manga-scheduled.index', $data);
       } catch (\Throwable $th) {
           return $th->getMessage();
       }
       }
   

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

}
