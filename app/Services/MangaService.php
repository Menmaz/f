<?php

namespace App\Services;

use Swoole\Table;
use Ophim\Core\Models\Manga;

class MangaService
{
    protected $table;

    public function __construct()
    {
        $this->table = new Table(1024);
        $this->table->column('id', Table::TYPE_INT);
        $this->table->column('title', Table::TYPE_STRING, 255);
        $this->table->column('slug', Table::TYPE_STRING, 255);
        $this->table->column('cover', Table::TYPE_STRING, 255);
        $this->table->column('views_sum_views', Table::TYPE_INT);
        $this->table->create();
    }

    public function getLatestMangas()
    {
        $mangas = Manga::orderBy('created_at', 'desc')
            ->select(['id', 'title', 'slug', 'cover'])
            ->withSum('views', 'views')
            ->take(20)
            ->get();

        foreach ($mangas as $manga) {
            $this->table->set($manga->id, [
                'id' => $manga->id,
                'title' => $manga->title,
                'slug' => $manga->slug,
                'cover' => $manga->cover,
                'views_sum_views' => $manga->views_sum_views,
            ]);
        }

        $mangasFromTable = [];
        foreach ($this->table as $key => $row) {
            $mangasFromTable[] = $row;
        }

        return $mangasFromTable;
    }
    
    public function getLatestUpdatedMangas($type)
    {
        $mangas = Manga::orderBy('updated_at', 'desc')
            ->select(['id', 'title', 'slug', 'cover'])
            ->with([
                'taxanomies' => function ($query) use ($type){
                    $query->whereIn('type', ['genre', 'status'])
                        ->where('slug', $type)
                        ->select('name', 'slug', 'type');
                },
            ])
            ->withSum('views', 'views')
            ->whereHas('taxanomies', function ($query) use ($type){
                $query->where('slug', $type);
            })
            ->paginate(12);

        return $mangas;
    }
}
