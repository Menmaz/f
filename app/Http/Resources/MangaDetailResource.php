<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MangaDetailResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        $taxanomies = $this->taxanomies()
                   ->whereIn("type", ["status", "genre", "type"])
                   ->select('name', 'slug', 'type')
                   ->get();
        $status = $taxanomies->where("type", "status")->first();
        $type = $taxanomies->where("type", "type")->first();
        $categories = $taxanomies->where("type", "genre")->values();

        return [
            'name' => $this->title ?? '',
            'slug' => $this->slug ?? '',
            'other_name' => explode(",", $this->alternative_titles ),
            'type_title' => $type->name ?? '',
            'type_slug' => $type->slug ?? '',
            'status_title' => $status->name ?? '',
            'status_slug' =>  $status->slug ?? '',
            // 'views' => $this->totalViews(),
            'views' => $this->getTotalViewsAttribute(),
            'content' => is_string($this->description)?strip_tags($this->description):"Đang cập nhật...",
            'status' => $this->status ?? '',
            'thumb_url' => $this->cover ?? '',
            'author' => $this->author ?? 'Đang cập nhật',
            'category' => $categories ?? [],
            'chapters' => $this->getChapters($this->chapters()->get(['title', 'chapter_number', 'created_at'])),
            'updatedAt' => DateHelper::localizeDate($this->updated_at)
        ];
    }

    // protected function averageStarRating()
    // {
    //     $totalRatings = $this->starRatings()->count();
    //     $sumRatings = $this->starRatings()->sum('rating');

    //     if ($totalRatings === 0) {
    //         return 0;
    //     }

    //     return $sumRatings / $totalRatings;
    // }

    protected function getChapters($chapters) {
        $uniqueChapters = $chapters
        ->unique('chapter_number'); 
    
        $serverData = $uniqueChapters->map(function ($chapter) {
            return [
                "chapter_name" => $chapter->chapter_number,
                "chapter_title" => $chapter->title ?? "",
                "created_at" => DateHelper::localizeDate($chapter->created_at),
            ];
        });
    
        return [
            "server_name" => "Server #1",
            "server_data" => $serverData->values(),
        ];
    }
}
