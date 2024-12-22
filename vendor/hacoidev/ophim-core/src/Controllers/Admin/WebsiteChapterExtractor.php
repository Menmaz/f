<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\Settings\app\Models\Setting;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class WebsiteChapterExtractor
{

    //LẤY DỮ LIỆU ẢNH TỪ CHAPTER TRÊN CÁC WEBSITE HỖ TRỢ
    protected $httpClient;
    protected $context;

    public function __construct()
    {
        $this->httpClient = new Client();
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Your_User_Agent_Here\r\n"
            ]
        ];
        $this->context = stream_context_create($opts);
    }

    public function extractChaptersWithUrl($url){
        $response = file_get_contents($url, false, $this->context);
        $crawler = new Crawler($response);

        switch (true) {
            case strpos($url, 'nettruyenfull.com') !== false:
                return $this->NettruyenfullChaptersExtractor($crawler);
            case strpos($url, 'doctruyenonline.vn') !== false:
                return $this->DoctruyenonlineChaptersExtractor($crawler);
                break;
            default:
        }
}

    //Nettruyenfull.com
    public function NettruyenfullChaptersExtractor($crawler){
        $extractedChapters = [];
        $crawler = $crawler->filter('#nt_listchapter');
        $chapterUrls = $crawler->filter('nav ul li div a')->extract(['href']);
        foreach($chapterUrls as $chapterUrl){
            $pattern = '/chap-(\d+(\.\d+)?)\//';
            preg_match($pattern, $chapterUrl, $matches);
            $chapterNumber = strval($matches[1]);
            $extractedChapters[] = [ "chapter_number" => $chapterNumber,
            "chapter_url" => $chapterUrl];
        }
        return $extractedChapters;
    }

    //Doctruyenonline.vn/truyen-tranh
    public function DoctruyenonlineChaptersExtractor($crawler){
        $extractedChapters = [];
        $crawler = $crawler->filter('#danh-sach-chuong');
        $chapterUrls = $crawler->filter('ul li a')->extract(['href']);
        foreach($chapterUrls as $chapterUrl){
            $pattern = '/chapter-(\d+(?:\.\d+)?)/';
            preg_match($pattern, $chapterUrl, $matches);
                $chapterNumber = strval($matches[1]);
                $extractedChapters[] = [
                    "chapter_number" => $chapterNumber,
                    "chapter_url" => "https://doctruyenonline.vn/".$chapterUrl
                ];
        }
        return $extractedChapters;
    }


    public function extractChapterImages($chapter_url){
            $response = file_get_contents($chapter_url, false, $this->context);
            $crawler = new Crawler($response);

            switch (true) {
                case strpos($chapter_url, 'nettruyenfull.com') !== false:
                    return $this->NettruyenfullChapterImagesExtractor($crawler);
                case strpos($chapter_url, 'doctruyenonline.vn') !== false:
                    return $this->DoctruyenonlineChapterImagesExtractor($crawler);
                    break;
                default:
            }
    }

    //Nettruyenfull.com
    public function NettruyenfullChapterImagesExtractor($crawler){
        $crawler = $crawler->filter('.reading-detail.box_doc');
        $imageUrls = $crawler->filter('div[id^="page_"] img')->extract(['data-original']);
        return $imageUrls;
    }

     //Doctruyenonline.vn/truyen-tranh
     public function DoctruyenonlineChapterImagesExtractor($crawler){
        $crawler = $crawler->filter('div');
        $imageUrls = $crawler->filter('img[loading^="lazy"]')->extract(['src']);
        return $imageUrls;
    }


}
