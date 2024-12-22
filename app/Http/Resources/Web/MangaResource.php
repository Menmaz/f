<?php

namespace App\Http\Resources\Web;

use App\Helpers\ViewHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class MangaResource extends JsonResource
{
    protected $perPage;

    public function __construct($resource, $perPage)
    {
        parent::__construct($resource);
        $this->perPage = $perPage;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $taxonomies = $this->taxanomies;

        $genres = $taxonomies->filter(function ($taxonomy) {
            return $taxonomy->type === 'genre';
        })->take(3)->map(function ($genre) {
            return [
                'name' => $genre->name,
                'slug' => $genre->slug,
            ];
        });

        $status = $taxonomies->firstWhere('type', 'status');
        if ($status) {
            $status = $status->name;
        } else {
            $status = "Đang phát hành";
        }

        $type = $genres->filter(function ($genre) {
            return in_array($genre['slug'], ['manga', 'manhua', 'manhwa', 'comic']);
        })->first() ? $genres->filter(function ($genre) {
            return in_array($genre['slug'], ['manga', 'manhua', 'manhwa', 'comic']);
        })->first()['name'] : 'New Chapter';

        $chapters = $this->chapters()
            ->select('chapter_number', 'created_at')
            ->distinct('chapter_number')
            ->orderBy('chapter_number', 'desc')
            ->take(3)
            ->get()
            ->map(function ($chapter) {
                return [
                    'chapter_number' => $chapter->chapter_number,
                    'created_at' => Carbon::parse($chapter->created_at)->diffForHumans(),
                ];
            });

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'cover' => $this->cover,
            'genres' => $genres,
            'status' => $status,
            'type' => $type,
            'chapters' => $chapters,
            'views' => ViewHelper::formatViews($this->views_sum_views),
            'rating_avg' => round(isset($this->star_ratings_avg_rating) ? $this->star_ratings_avg_rating : 0),
            'rating_count' => $this->star_ratings_count ?? 0
        ];
    }

    /**
     * Create a paginated collection of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Collection  $mangas
     * @param  \Illuminate\Support\Collection  $taxonomies
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function collectionWithPagination($request, $mangas, $perPage)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $mangas->forPage($currentPage, $perPage),
            $mangas->total(),
            $perPage,
            $currentPage,
            ['path' => $request->url()]
        );

        return $paginator->setCollection($mangas->map(function ($manga) use ($perPage) {
            return (new static($manga, $perPage))->toArray(request());
        }));
    }
}
