<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class MangaResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request)
    {   
        $latestChapter = $this->chapters->first();
        if ($latestChapter) {
            $latestChapter->updateAt = DateHelper::getLocalizedDiffForHumans($latestChapter->created_at) ?? $latestChapter->created_at;
        } else {
            $latestChapter = (object) [
                'chapter_number' => '',
                'updateAt' => '-',
            ];
        }

        $status = $this->taxanomies->where('type', 'status')->first();
        $type = $this->taxanomies->where('type', 'type')->first();
        $categories = $this->taxanomies->where('type', 'genre')->values();
      
        return [
            'name' => $this->title ?? '',
            'other_name' => $this->alternative_titles ?? '',
            'origin_name' => $this->alternative_titles ?? '',
            'slug' => $this->slug ?? '',
            'thumb_url' => $this->cover ?? '',
            'updatedAt' => DateHelper::localizeDate($this->updated_at) ?? '',
            'updated_at' => $this->updated_at ?? '',
            'description' => is_string($this->description)?strip_tags($this->description):"Đang cập nhật...",
            'type_name' => $type->name ?? 'New Chapter',
            'type_title' => $type->name ?? '',
            'type_slug' => $type->slug ?? '',
            'status_name' => $status->name ?? '',
            'status_title' => $status->name ?? '',
            'status_slug' =>  $status->slug ?? '',
            'views' => $this->getTotalViewsAttribute(),
            'chapter_number_latest' => $latestChapter->chapter_number ?? '',
            'category' => $categories ?? [],
            'chaptersLatest' => $this->get5LatestChapter($this->chapters),
        ];
    }

    protected function getCategories($categories){
        $formattedCategories = $categories->map(function ($category) {
            return [
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => $category->type,
            ];
        });

        return $formattedCategories;
    }

    protected function get5LatestChapter($latestChapters)
{
    $formattedChapters = $latestChapters->unique('chapter_number')->take(5)->map(function ($chapter) {
        return [
            'chapter_name' => $chapter->chapter_number,
            'updateAt' => DateHelper::localizeDate($chapter->created_at),
        ];
    });

    return $formattedChapters->toArray();
}

}