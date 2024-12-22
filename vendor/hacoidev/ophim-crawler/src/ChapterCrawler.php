<?php

namespace Ophim\Crawler\OphimCrawler;

use Illuminate\Support\Facades\Http;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Chapter;
use Exception;
use GuzzleHttp\Client;
use Ophim\Core\Controllers\Admin\ImageStorageManager;
use Ophim\Crawler\OphimCrawler\Contracts\BaseCrawler;
use GuzzleHttp\Promise\Utils;

class ChapterCrawler
{
    protected $contextConnection;
    protected $manga;
    protected $chapter;
    protected $logger;
    protected $imageStorageManager;
    protected $client;
    
    public function __construct($manga, $chapter, $logger){
        $this->contextConnection = stream_context_create([
            'http' => [
                'timeout' => 30, // Adjust the timeout value as needed
            ],
            'ssl' => [
                'timeout' => 30, // Adjust the SSL timeout value as needed
            ],
        ]);
        
        $this->manga = $manga;
        $this->chapter = $chapter;
        $this->logger = $logger;
        $this->imageStorageManager = new ImageStorageManager();
        $this->client = new Client();
    }

    public function handle()
    {
        $this->uploadChaptersToStorage($this->manga, $this->chapter);
    }

    protected function getPageNumberOfChapterUrl($chapterUrl){
        if (preg_match('/(\d+(?:\.\d+)?)\.jpg/', $chapterUrl, $matches)) {
            $chapter_number = $matches[1];
            return $chapter_number;
        } else {
            $randomString = uniqid();
            return $randomString;
        }
    }

    // protected function getPageNumberOfChapterUrl($pageImageUrl)
    // {
    // // Logic để lấy số trang từ URL, ví dụ:
    // // Giả sử URL có định dạng như: "http://example.com/manga/chapter_1/page_2.jpg"
    // $pathParts = explode('/', parse_url($pageImageUrl, PHP_URL_PATH));
    // $pagePart = end($pathParts);
    // preg_match('/page_(\d+)/', $pagePart, $matches);
    // return $matches[1] ?? null;
    // }

//basic
// protected function uploadChaptersToStorage($manga, $chapter)
// {
//     $new_content_sv2 = [];
//     $chapterNumber = $chapter->chapter_number;
//     $convertedToWebpImages = [];
//     $start_time = microtime(true);

//     // $this->logger->notice(sprintf("Đang tải các ảnh từ chapter %s và chuyển đổi sang định dạng", $chapterNumber));
//     $pageUrls = $chapter->content;

//     foreach ($pageUrls as $pageImageUrl) {
//         $response = $this->client->get($pageImageUrl);  
//         $statusCode = $response->getStatusCode();
//         if($statusCode == 200){
//             $imageData = $response->getBody();
//             $convertedToWebpImage = $this->imageStorageManager->convertToWebP($imageData);
//             $convertedToWebpImages[] = $convertedToWebpImage;
//             // $pageNumber = $this->getPageNumberOfChapterUrl($pageImageUrl);

//             // Lấy số trang từ URL
//             // $pageImageUrl = $pageUrls[$key];
//             $pageNumber = $this->getPageNumberOfChapterUrl($pageImageUrl);

//             $contaboPrefix = "/uploads/manga/{$manga->slug}/chapter_$chapterNumber/page_$pageNumber.webp";
    //             $uploadImageToContaboUrl = $this->imageStorageManager->uploadImageToContabo($contaboPrefix, $convertedToWebpImage);
    //             $new_content[] = $uploadImageToContaboUrl;
                
    //             $pixeldrainPrefix = "/{$this->manga->slug}"."_chapter_$chapterNumber"."_page_$pageNumber.webp";
    //             $uploadImageToPixeldrainUrl = $this->imageStorageManager->uploadImageToPixeldrain($pixeldrainPrefix, $convertedToWebpImage);
    //             $new_content_sv2[] = $uploadImageToPixeldrainUrl;

//             // Upload image to Backblaze storage
//             $backblazePrefix = "/uploads/manga/{$manga->slug}/chapter_$chapterNumber/page_{$pageNumber}.webp";
//             $new_content_sv2[] = $this->imageStorageManager->uploadImageToBackblaze($backblazePrefix, $convertedToWebpImage);
//         }
//     }
//     // Fill and save chapter data
//     if (!empty($new_content_sv2)) {
//         $chapter->fill([
//             'content_sv2' => $new_content_sv2,
//             'status' => 'uploaded_to_storage'
//         ])->save();

//         $end_time = microtime(true);
//         $execution_time = round($end_time - $start_time, 2);
//         $this->logger->notice(sprintf("==> Hoàn tất upload lên storage cho chapter %s của truyện '%s' trong thời gian %s giây", $chapterNumber, $manga->title, $execution_time));
//         printf("==> Hoàn tất upload lên storage cho chapter %s của truyện '%s' trong thời gian %s giây", $chapterNumber, $manga->title, $execution_time);
//     } else {
//         // $this->logger->error(sprintf("Không có dữ liệu hình ảnh được tải lên hoặc lưu trữ cho chapter %s của truyện '%s' trong thời gian %s giây", $chapterNumber, $manga->title));
//         // printf("Không có dữ liệu hình ảnh được tải lên hoặc lưu trữ cho chapter %s của truyện '%s' trong thời gian %s giây", $chapterNumber, $manga->title);
//     }
// }

protected function uploadChaptersToStorage($manga, $chapter)
{
    $chapterNumber = $chapter->chapter_number;
    $start_time = microtime(true);

    $this->synchronous($manga, $chapter);

    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);
    printf("==> Đã upload chapter %s của truyện '%s' trong %s giây\n", $chapterNumber, $manga->title, $execution_time);
}

//xử lý chapter (bất đồng bộ)
protected function synchronous($manga, $chapter)
{
    $client = $this->client;
    $content_sv2 = [];

    $pageUrls = $chapter->content;
    $chapterNumber = $chapter->chapter_number;

    $concurrency = 10; // Số lượng yêu cầu đồng thời tối đa
    
    usort($pageUrls, function($a, $b) {
        preg_match('/page_(\d+)\.jpg$/', $a, $matchesA);
        preg_match('/page_(\d+)\.jpg$/', $b, $matchesB);
        return $matchesA[1] - $matchesB[1];
    });

    $requests = function () use ($client, $pageUrls) {
        foreach ($pageUrls as $pageUrl) {
                yield function () use ($client, $pageUrl) {
                    return $client->getAsync($pageUrl);
                };
        }
    };

    $pool = new \GuzzleHttp\Pool($client, $requests(), [
        'concurrency' => $concurrency,
        'fulfilled' => function ($response, $index) use ($manga, $pageUrls, $chapterNumber, &$content_sv2) {
            if ($response->getStatusCode() === 200) {
                $imageData = $response->getBody();
                // $convertedToWebpImage = $this->imageStorageManager->convertToWebP($imageData);
    
                $pageUrl = $pageUrls[$index];
                $pageNumber = $this->getPageNumberOfChapterUrl($pageUrl);
    
                // Upload image to Backblaze storage
                $backblazePrefix = "/uploads/manga/{$manga->slug}/chapter_$chapterNumber/page_{$pageNumber}.webp";
                // $content_sv2[] = $this->imageStorageManager->uploadImageToBackblaze($backblazePrefix, $convertedToWebpImage);
                $content_sv2[] = $backblazePrefix;
        }
        }
    ]);

    $promise = $pool->promise();
    $promise->wait(); // Đợi tất cả các yêu cầu hoàn thành

    // if (!empty($content_sv2)) {
    //     $chapter->fill([
    //         'content_sv2' => $content_sv2,
    //         'status' => 'uploaded_to_storage'
    //     ])->save();
    // }

    print_r($content_sv2);
     
    $promise = null;
}

}
