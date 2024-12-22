<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Setting;
use Ophim\Core\Models\Taxonomy;

class ChapterIndex extends Component
{
    public $slug;
    public $chapter_number;

    public $seoData;
    public $categories;
    public $manga;
    public $currentChapter;
    public $chapters;

    public function mount($slug, $chapter_number)
    {
        $this->slug = $slug;
        $this->chapter_number = $chapter_number;
        $this->loadData();
    }

    public function updateChapter($chapterNumber)
    {
        $this->chapter_number = $chapterNumber;
        $this->loadData();
    }

    protected function getSeoData($manga, $chapter_number)
    {
        $settings = Setting::whereIn('key', [
            'site_episode_watch_title',
            'site_tag_key',
            'site_meta_shortcut_icon',
            'site_meta_head_tags'
        ])->get()->keyBy('key');

        $titleHead = str_replace(
                ['{manga.name}', '{manga.origin_name}', '{manga.chapter_current}'],
                [$manga->title, ('- ' . $manga->alternative_titles), $chapter_number ?? 'mới nhất'],
                $settings->get('site_episode_watch_title')->value
        );
        $seoData['titleHead'] = preg_replace('/\s+/', ' ', trim($titleHead));
        $seoData['og_image'] = $manga->cover;
        
        $seoData['descriptionHead'] = $manga->description;
        $keywordsHeadTemp = $settings->get('site_tag_key')->value;
        $keywordsHead = str_replace(
            ['{name}'],
            [$manga->title . " tập $chapter_number" ?? ''],
            $keywordsHeadTemp
        );
        $seoData['keywordsHead'] = $keywordsHead;
        $seoData['og_url'] = config('custom.frontend_url') . "/doc-truyen/$manga->slug/$chapter_number";
        $seoData['icon'] = $settings->get('site_meta_shortcut_icon')->value;
        $seoData['site_meta'] = $settings->get('site_meta_head_tags')->value;

        return $seoData;
    }

    protected function loadData()
    {
        $this->manga = Manga::where('slug', $this->slug)
        ->with(['chapters' => function ($query) {
            $query->select('manga_id', 'content', 'content_sv2', 'chapter_number')
                  ->orderByDesc('chapter_number');
        }])
        ->first(['id', 'title', 'slug', 'cover']);
        $this->currentChapter = $this->manga->chapters
                                      ->where('chapter_number', $this->chapter_number)
                                      ->first();
        $this->seoData = $this->getSeoData($this->manga, $this->currentChapter->chapter_number);
    }

    protected function getCategories()
    {           
        return Cache::remember('categories', now()->addHours(8), function () {
            return Taxonomy::where("type", 'genre')->orWhere('type', 'type')->orderBy('name', 'asc')->get(["name", "slug", "seo_des as description"]);
        });
    }

    public function render()
    {
        $this->categories = $this->getCategories();
        $this->chapters = $this->manga->chapters;
        return view('frontend-web.chapter.index_old');
    }
}
