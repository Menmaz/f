<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Manga;

class MangaDetailController extends Controller
{
    public function index($slug)
    {
        $cacheKey = 'manga_' . $slug;

        $manga = Cache::rememberForever($cacheKey, function () use ($slug) {
          return Manga::where('slug', $slug)
            ->with([
                'taxanomies' => function ($query) {
                   return $query->select('name', 'slug', 'type');
                },
                'chapters' => function ($query) {
                   return $query
                    ->orderBy('chapter_number', 'desc')
                    ->select('manga_id', 'chapter_number', 'created_at')
                    ->distinct('chapter_number');
                }
            ])
            ->withCount('starRatings')
            ->select(['id', 'title', 'alternative_titles', 'slug', 'cover', 'description', 'author', 'updated_at'])
            ->withSum('views', 'views')
            ->withAvg('starRatings', 'rating')
            ->firstOrFail();
        });
       
        if (!$manga) {
            abort(404);
        }
        
        $manga->addView();

        $relativeMangas = Manga::select(['title', 'slug'])
                        ->where('id', '!=', $manga->id)
                        ->inRandomOrder()
                        ->take(5)
                        ->get();

        $latestMangas = Cache::remember('latest_mangas_' . $manga->id, 30, function () use ($manga) {
            return Manga::select(['title', 'slug', 'cover'])
                ->where('id', '!=', $manga->id)
                ->take(5)
                ->withMax('chapters', 'chapter_number')
                ->get();
        });

        $data = [
            'seoData' => $this->getSeoData($manga, $manga->chapters->first()->chapter_number),
            'manga' => $manga,
            'relativeMangas' => $relativeMangas,
            'latestMangas' => $latestMangas,
            ];

            return view('frontend-web.manga-detail.index')->with($data);;
    }

    public function random()
    {
        $randomManga = DB::table('mangas')->inRandomOrder()->select('slug')->first();
        if ($randomManga) {
            return redirect()->route('manga.detail', ['slug' => $randomManga->slug]);
        }
    }

    public function fetchChapters($slug)
    {
        try {
        $manga = Manga::where('slug', $slug)->firstOrFail();

        $chapters = $manga->chapters()
            ->select('chapter_number', 'created_at')
            ->groupBy('chapter_number','created_at')
            ->orderBy('chapter_number', 'desc')
            ->get();

        return response()->json($chapters);
        } catch (\Throwable $th) {
             return response()->json($th->getMessage(), 500);
        }
    }

    protected function getSeoData($manga, $latestChapterNumber)
    {
        $settings = $this->getSeoSettings();
        $titleHeadTemp = $settings->get('site_movie_title')->value;
        $seoData['title'] = str_replace(
            ['{name}', '{origin_name}', '{episode_chapter}'],
            [$manga->title ?? '', $manga->alternative_titles ?? '', $latestChapterNumber ?? 'mới nhất'],
            $titleHeadTemp
        );
        $seoData['head_tags_meta'] = $settings->get('site_meta_head_tags')->value;
        $seoData['keywords_meta'] = str_replace(
            ['{name}'],
            [$manga->title ?? ''],
            $settings->get('site_tag_key')->value
        );

        $description = $manga->description;
        if (str_ends_with($description, '...')) {
            $description = substr($description, 0, -3);
        }
        $seoData['description_meta'] = mb_substr($description, 0, 156);
        $seoData['image_meta'] = $manga->cover;
        $seoData['icon'] = asset($settings->get('site_meta_shortcut_icon')->value);
        $seoData['site_script'] = $settings->get('site_scripts_google_analytics')->value;

        return $seoData;
    }

}
