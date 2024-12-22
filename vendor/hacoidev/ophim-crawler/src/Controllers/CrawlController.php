<?php

namespace Ophim\Crawler\OphimCrawler\Controllers;

use App\Console\Commands\NettruyenCommands\NettruyenCrawlerCommand;
use App\Console\Commands\TruyenqqvietCrawlerCommand;
use App\Console\Commands\TruyenVNCommands\TruyenVNCrawlerCommand;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Ophim\Crawler\OphimCrawler\NettruyenCrawler;
use Ophim\Crawler\OphimCrawler\TruyenqqCrawler;
use Ophim\Crawler\OphimCrawler\TruyenVNCrawler\TruyenVNCrawler;

use function PHPUnit\Framework\isEmpty;

/**
 * Class CrawlController
 * @package Ophim\Crawler\OphimCrawler\Controllers
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CrawlController extends CrudController
{
    private $websiteDomainsForCrawl = [
        'nettruyenhe.com' => [
            'mangaListCrawler' => NettruyenCrawlerCommand::class,
            'mangaDetailCrawler' => NettruyenCrawler::class,
        ],
        'truyenvn.me' => [
            'mangaListCrawler' => TruyenVNCrawlerCommand::class,
            'mangaDetailCrawler' => TruyenVNCrawler::class,
        ], 
        'truyenqqviet.com' => [
            'mangaListCrawler' => TruyenqqvietCrawlerCommand::class,
            'mangaDetailCrawler' => TruyenqqCrawler::class
        ]
    ];

    private function getCrawlerClassForDomain($url, $type)
    {
        foreach ($this->websiteDomainsForCrawl as $domain => $crawlers) {
            if (strpos($url, $domain) !== false && isset($crawlers[$type])) {
                $crawlerClass = $crawlers[$type];
                return new $crawlerClass($url);
            }
        }
        return null;
    }

    private function getMangaDetail($link)
    {
        $mangaDetailCrawler = $this->getCrawlerClassForDomain($link, 'mangaDetailCrawler');
        if ($mangaDetailCrawler) {
            $mangaDataResponse = $mangaDetailCrawler->getMangaDetailData($link);
            if (isset($mangaDataResponse['data']['item'])) {
                $itemData = $mangaDataResponse['data']['item'];
                $itemData['slug'] = $itemData['name'];
                $itemData['url'] = $link;
                return collect($itemData)->only('name', 'url', 'slug')->toArray();
            }
        }
        return null;
    }

    private function getMangaList($link)
    {
        $mangaListCrawler = $this->getCrawlerClassForDomain($link, 'mangaListCrawler');
        if ($mangaListCrawler) {
            $items = $mangaListCrawler->getMangaList($link);
            if (!empty($items)) {
                $data = collect();
                foreach ($items as $item) {
                    $mangaDataResponse = $mangaListCrawler->getMangaItemData($item);
                    if (isset($mangaDataResponse['data']['item'])) {
                        $itemData = $mangaDataResponse['data']['item'];
                        $itemData['slug'] = $itemData['name'];
                        $itemData['url'] = $link;
                        $data->push(collect($itemData)->only('name', 'slug', 'url')->toArray());
                    }
                }
                return $data;
            }
        }
        return collect();
    }

    public function fetch(Request $request)
    {
        try {
            $data = collect();
            $links = preg_split('/[\n\r]+/', $request->input('link', ''));

            foreach ($links as $link) {
                if (preg_match('/(.*?\/truyen-tranh\/[^\/]+)/', $link)) {
                    $item = $this->getMangaDetail($link);
                    if ($item) {
                        $data->push($item);
                    }
                } else {
                    $listData = $this->getMangaList($link);
                    $data = $data->merge($listData);
                }
            }

            $uniqueData = $data->unique('slug')->values();
            return response()->json($uniqueData->shuffle());

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function showCrawlPage(Request $request)
    {
        try {
        if (!backpack_user()->hasRole('Admin')) {
            abort(403);
        }
        try {
           
            
        } catch (\Throwable $th) {
            return $th->getMessage();
        }

        // $fields = $this->movieUpdateOptions();
        $fields = [];

        return view('ophim-crawler::crawl');
    } catch (\Throwable $th) {
        return response()->json($th->getMessage(), 500);
    }
    }

    public function crawl(Request $request)
    {
        try {
            // $link = str_replace('{slug}', $request['slug'], $pattern);
            $link = $request->input('url');
            $mangaDetailCrawler = $this->getCrawlerClassForDomain($link, 'mangaDetailCrawler');
            if (!$mangaDetailCrawler) {
                return response()->json(['message' => 'Invalid domain', 'wait' => false], 400);
            }
            $mangaDetailCrawler->handle();
            return response()->json(['message' => 'OK', 'wait' => true], 200);

        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage(), 'wait' => false], 404);
        }
    }
   
}
