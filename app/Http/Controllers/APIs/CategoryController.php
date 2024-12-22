<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\APIs\Contracts\ApiBase ;
use App\Http\Resources\MangaResource;
use App\Http\Resources\SeoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;

class CategoryController extends ApiBase
{

    protected function getTaxonomies(Request $request, $type)
    {   
        try {
            $resultQuery = Taxonomy::where("type", $type);
            if ($request->filled("filter")) {
                $title = $request->input("filter");

                $resultQuery->where("name", "LIKE", "%" . $title . "%")
                    ->orWhere("slug", "LIKE", "%" . $title . "%");
            }

            $resultQuery = $resultQuery->orderBy('name', 'asc')->get(["name", "slug", "seo_des as description"]);
            $data['items'] = $resultQuery;
            return $this->response(["data"=>$data]);
        } catch (\Throwable $th) {
            return $this->response(["message" => $th->getMessage()], 500);
        }
    }

    public function getCategories(Request $request)
    {   
        // return $this->getTaxonomies($request, "genre");
        
        $cacheKey = 'categories.' . ($request->filled('filter') ? md5($request->input('filter')) : 'all');
        $ttl = 60; // Cache for 60 minutes (adjust as needed)

        if (Cache::has($cacheKey)) {
            return $this->response(['data' => Cache::get($cacheKey)]);
        }
        try {
            $resultQuery = Taxonomy::where("type", 'genre')->orWhere('type', 'type');
            if ($request->filled("filter")) {
                $title = $request->input("filter");

                $resultQuery->where("name", "LIKE", "%" . $title . "%")
                    ->orWhere("slug", "LIKE", "%" . $title . "%");
            }

            $resultQuery = $resultQuery->orderBy('name', 'asc')->get(["name", "slug", "seo_des as description"]);
            $data['items'] = $resultQuery;
            
            Cache::put($cacheKey, $data, $ttl); // Cache the results
            
            return $this->response(["data"=>$data]);
        } catch (\Throwable $th) {
            return $this->response(["message" => $th->getMessage()], 500);
        }
    }

    public function getTypes(Request $request)
    {   
        // return $this->getTaxonomies($request, "type");
        $data['items'] = [];
        return $this->response(["data"=>$data]);
    }

    public function getStatuses(Request $request)
    {   
        return $this->getTaxonomies($request, "status");
    }

    public function getMenu()
    {
        try {
            $categories = Taxonomy::where("type", "genre")
    ->get(["name", "slug"])
    ->transform(function ($item) {
        $item->href = "/the-loai/" . $item->slug;
        unset($item->slug); // Loại bỏ cột 'slug' nếu bạn không cần nó nữa
        return $item;
    });
        $NAV_ITEMS = [
            ["title" => "Thể loại", "href" => "", "children" => $categories],
            // ["title" => "Danh sách", "href" => "", "children" =>
            //     [
            //         ["title" => "Truyện mới", "href" => "/danh-sach/truyen-moi"],
            //         ["title" => "Truyện mới cập nhật", "href" => "/danh-sach/moi-cap-nhat"],
            //         ["title" => "Đang phát hành", "href" => "/danh-sach/dang-phat-hanh"],
            //         ["title" => "Hoàn thành", "href" => "/danh-sach/hoan-thanh"],
            //         ["title" => "Sắp ra mắt", "href" => "/danh-sach/sap-ra-mat"],
            //         ["title" => "Manga", "href" => "/danh-sach/manga"],
            //         ["title" => "Manhua", "href" => "/danh-sach/manhua"],
            //         ["title" => "Manhwa", "href" => "/danh-sach/manhwa"]
            //     ]],
            ["title" => "Lịch truyện", "href" => "/lich-truyen", "children" => []],
            ["title" => "tin tức", "href" => "", "children" => []],
            ["title" => "Xem Anime", "href" => "https://10anime.com", "children" => []],
            ["title" => "Group", "href" => "", "children" => []],
            ["title" => "Fanpage", "href" => "", "children" => []]
        ];

        $data = [
            "NAV_ITEMS" => $NAV_ITEMS
        ];
        return $this->response(["data"=>$data]);
        } catch (\Throwable $th) {
            return $this->response(["message" => $th->getMessage()], 500);
        }
    }

}
