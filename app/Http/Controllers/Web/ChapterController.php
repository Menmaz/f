<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;
use Ophim\Core\Models\ChapterReport;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function index($slug, $chapter_number)
    {
        $manga = Manga::where('slug', $slug)
            ->select(['id', 'title', 'slug', 'cover', 'description'])
            ->with(['chapters' => function ($query) {
                $query->select('id', 'manga_id', 'content', 'content_sv2', 'chapter_number')
                    ->orderByDesc('chapter_number');
            }])
            ->first();
    
        if(!$manga){
            abort(404);
        }
    
        $manga->addView();
    
        $chapters = $manga->chapters;
    
        $currentChapter = $chapters->where('chapter_number', $chapter_number)->first();
    
        $previousChapter = $manga->chapters
            ->where('chapter_number', '<', $currentChapter->chapter_number)
            ->sortByDesc('chapter_number')
            ->first();
    
        $nextChapter = $manga->chapters
            ->where('chapter_number', '>', $currentChapter->chapter_number)
            ->sortBy('chapter_number')
            ->first();
    
        if (!session()->has('reading_mangas')) {
            session(['reading_mangas' => []]);
        }
    
        $readingMangas = session('reading_mangas');
        $mangaExists = collect($readingMangas)->contains(function ($value, $key) use ($manga, $chapter_number) {
            return $value['manga_id'] == $manga->id && $value['current_chapter'] == $chapter_number;
        });
    
        if (!$mangaExists) {
            $readingMangas[] = [
                'session_id' => uniqid(),
                'manga_id' => $manga->id,
                'title' => $manga->title,
                'slug' => $manga->slug,
                'cover' => $manga->cover,
                'current_chapter' => $currentChapter->chapter_number,
                'total_chapters' => $manga->chapters->first()->chapter_number,
            ];
            session(['reading_mangas' => $readingMangas]);
        }
    
        $data['manga'] = $manga;
        $data['chapters'] = $chapters;
        $data['currentChapter'] = $currentChapter;
        $data['previousChapter'] = $previousChapter;
        $data['nextChapter'] = $nextChapter;
        $data['categories'] = $this->getCategories();
        $data['seoData'] = $this->getSeoData($manga, $currentChapter->chapter_number);
    
        return view('frontend-web.chapter.index', $data);
    }

    protected function getSeoData($manga, $chapter_number)
    {
        $settings = $this->getSeoSettings();
        $seoData['title'] = str_replace(
                ['{manga.name}', '{manga.origin_name}', '{manga.chapter_current}'],
                [$manga->title, ('- ' . $manga->alternative_titles), $chapter_number ?? 'mới nhất'],
                $settings->get('site_episode_watch_title')->value
        );
        $seoData['keywords_meta'] = str_replace(
            ['{name}'],
            [$manga->title . " chapter $chapter_number" ?? ' mới nhất'],
            $settings->get('site_tag_key')->value
        );
        $seoData['head_tags_meta'] = $settings->get('site_meta_head_tags')->value;
        $description = $manga->description;
        if (str_ends_with($description, '...')) {
            $description = substr($description, 0, -3);
        }
        $seoData['description_meta'] = mb_substr($description, 0, 156);
        $seoData['image_meta'] = $manga->cover;
        $seoData['site_script'] = $settings->get('site_scripts_google_analytics')->value;

        return $seoData;
    }

    protected function getCategories()
    {           
        return Cache::remember('categories', now()->addHours(8), function () {
            return Taxonomy::where("type", 'genre')->orWhere('type', 'type')->orderBy('name', 'asc')->get(["name", "slug", "seo_des as description"]);
        });
    }
    
    public function reportChapter(Request $request){
        try {
            $chapterId = $request->input('chapter_id');
            $reportMessage = $request->input('report_message');

            if (strlen($reportMessage) < 20) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nội dung báo lỗi phải có ít nhất 20 ký tự.',
                ]);
            }
    
            if (strlen($reportMessage) > 500) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nội dung báo lỗi không được vượt quá 500 ký tự.',
                ]);
            }

        ChapterReport::create([
            'chapter_id' => $chapterId,
            'report_message' => $reportMessage,
        ]);

        return response()->json(['message' => 'Báo lỗi thành công']);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()]);
    }
    }

}
