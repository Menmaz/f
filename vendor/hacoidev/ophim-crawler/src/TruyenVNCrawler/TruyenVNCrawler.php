<?php

namespace Ophim\Crawler\OphimCrawler\TruyenVNCrawler;

use App\Helpers\CurlHelper;
use App\Helpers\ImageHelper;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ophim\Core\Models\Taxable;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;
use Symfony\Component\DomCrawler\Crawler;
use Ophim\Core\Controllers\Admin\ImageStorageManager;
use Illuminate\Support\Str;
use Ophim\Core\Models\View;

class Collector
{
    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function get(): array
    {
        $dataItem = $this->payload['data']['item'];
        $info = $dataItem ?? [];

        if (strpos($info['content'], 'KhoTruyen') !== false) {
            // Nếu chứa 'KhoTruyen'
            $info['content'] = str_replace('KhoTruyen', 'Trieu Bui', $info['content']);
        }
        
        if (strpos($info['content'], 'TruyenVN') !== false) {
            // Nếu chứa 'TruyenVN'
            $info['content'] = str_replace('TruyenVN', 'Trieu Bui', $info['content']);
        }

        $data = [
            'title' => $info['name'],
            'origin_name' => $info['origin_name'],
            'slug' => $info['slug'],
            'description' => $info['content'],
            'author' => $info['author'],
            'status' => $info['status'],
            'cover' => $info['thumb_url'],
            'created_at' => $info['createdAt'],
            // 'updated_at' => $info['updatedAt']
        ];

        return $data;
    }
}

class TruyenVNCrawler
{
    public static function fetchHtmlViaProxyChapterData($url)
    {
            try {
                $client = new Client();
                $response = $client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                        'Referer' => 'https://truyenvn.me',
                    ],
                    'timeout' => 200,
                    'connect_timeout' => 200,
                ]);

                $htmlContent = (string) $response->getBody();

                return $htmlContent; 
            } catch (RequestException $e) {
                // continue;
            }

        return null;
    }

    public static function fetchHtmlViaProxyMangaData($url)
    {
        $api_url = 'https://api.proxyscrape.com/v3/accounts/freebies/scraperapi/request';
        $apiKey = 'fad59b12-0017-4ade-9ebe-560498e39457';
            
        $data = json_encode(array(
                "url" => $url,
                "browserHtml" => true,
                "actions" => [
                    array(
                        "action" => "waitForSelector",
                        "selector" => [
                            "type" => "css",
                            "value" => ".main.version-chap.no-volumn.active"
                        ],
                        "timeout" => 15
                    )
                ]
        ));
            
        $ch = curl_init();
            
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            
        $headers = array(
                'Content-Type: application/json',
                'X-Api-Key: ' . $apiKey
        );
            
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false) {
            echo 'Curl error: ' . curl_error($ch);
        } else {
            $responseData = json_decode($response, true);
                
            if (isset($responseData['data']['browserHtml'])) {
                return $responseData['data']['browserHtml'];
            } elseif (isset($response_data['data']['httpResponseBody'])) {
                return base64_decode($response_data['data']['httpResponseBody']);
            } else {
                echo 'Không có dữ liệu hợp lệ trong phản hồi.';
            }
        }
    }


    //lấy dữ liệu từ trang chi tiết truyện
    public function getMangaDetailData($url)
    {
        try {
        // $body = CurlHelper::fetchHtmlViaProxy($url);
        $body = $this->fetchHtmlViaProxyMangaData($url);
        $crawler = new Crawler($body);
        $manga = [];

        $manga['name'] = $crawler->filterXPath('.//div[@class="post-title"]')->text() ?? '';
        $manga['slug'] = basename(parse_url($url, PHP_URL_PATH));

        // Extracting tên khác
        $alternateNamesNode = $crawler->filterXPath('//div[@class="post-content_item"]//div[@class="summary-heading"]/h5[contains(text(), "Tên khác")]');
        if ($alternateNamesNode->count() > 0) {
            $manga['origin_name'] = trim($alternateNamesNode->parents()->filter('.summary-content')->text());
        } else {
            $manga['origin_name'] = '';
        }

        $contentNode = $crawler->filterXPath('.//div[@class="description-summary"]');
        if($contentNode->count() > 0){
             $manga['content'] = $contentNode->text() ?? '';
        } else {
            $manga['content'] = '';
        }

        $categoryNamesNode =$crawler->filterXPath('.//div[@class="genres-content"]');
        if ($categoryNamesNode->count() > 0) {
            $manga['category'] = $categoryNamesNode->filterXPath('.//a')->each(function (Crawler $node){
            $genreName = $node->text() ?? '';
                $slug = Str::slug($genreName);
                return [
                    'name' => $genreName,
                    'slug' => $slug,
                ];
            });
        } else {
            $manga['category'] = [];
        }

        foreach ($manga['category'] as $category) {
            if (in_array($category['name'], ['truyện tranh 18', '18+', 'hentai'])) {
                return null;
            }
        }

        $manga['status_name'] = "Đang phát hành";
        $manga['status'] = 'ongoing'; 

        $manga['thumb_url'] = $crawler->filterXPath('//div[@class="summary_image"]/img')->attr('src') ?? '';
        $manga['author'] = 'Đang cập nhật';
        $manga['views'] = $this->findViewCounts($crawler);

        $manga['chapters'] = $crawler->filterXPath('.//li[@class="wp-manga-chapter    "]')->each(function (Crawler $node){
                $chapterName = $node->filterXPath('.//a')->text() ?? '';
                if(preg_match('/Chapter (\d+(\.\d+)?)/', $chapterName, $matches)){
                    $chapterNumber = $matches[1] ?? null;
                    $chapterUrl = $node->filterXPath('.//a')->attr('href') ?? '';
                    $chapterTime = $node->filterXPath('.//span')->text() ?? '';
                    $chapterTime = $this->convertRelativeTimeToDateTime($chapterTime);
                    return [
                            'filename' => '',
                            'chapter_name' => $chapterNumber,
                            'chapter_api_data' => $chapterUrl,
                            'chapter_updated_at' => $chapterTime,
                            ];
                }
        }) ?? [];

        usort($manga['chapters'], function ($a, $b) {
            return $a['chapter_name'] <=> $b['chapter_name'];
        });
            
        $manga['createdAt'] = $manga['chapters'] ? $manga['chapters'][0]['chapter_updated_at'] : null;
        
        if($manga){
            return [
            "data" => [
                'item' => $manga,
            ]
        ];
        }
        
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage()
        ];
    }
    }

    //chuyển đổi thời gian
    protected function convertRelativeTimeToDateTime($relativeTime) {
        // Lấy ngày giờ hiện tại
        $currentDateTime = Carbon::now();
        
        // Kiểm tra xem chuỗi có phải là một ngày cụ thể không (định dạng dd/mm/yyyy)
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $relativeTime, $dateMatches)) {
            $day = intval($dateMatches[1]);
            $month = intval($dateMatches[2]);
            $year = intval($dateMatches[3]);
            $specificDate = Carbon::create($year, $month, $day);
            
            return $specificDate->format('Y-m-d H:i:s');
        }
    
        // Phân tích chuỗi để lấy số lượng và đơn vị thời gian
        if (preg_match('/(\d+) (phút|giờ|ngày|tháng|năm) trước/', $relativeTime, $matches)) {
            $number = intval($matches[1]);
            $unit = $matches[2];
    
            switch ($unit) {
                case 'phút':
                    $chapterDateTime = $currentDateTime->copy()->subMinutes($number);
                    break;
                case 'giờ':
                    $chapterDateTime = $currentDateTime->copy()->subHours($number);
                    break;
                case 'ngày':
                    $chapterDateTime = $currentDateTime->copy()->subDays($number);
                    break;
                case 'tháng':
                    $chapterDateTime = $currentDateTime->copy()->subMonths($number);
                    break;
                case 'năm':
                    $chapterDateTime = $currentDateTime->copy()->subYears($number);
                    break;
                default:
                    $chapterDateTime = $currentDateTime;
            }
    
            // Format ngày giờ thành định dạng mong muốn
            return $chapterDateTime->format('Y-m-d H:i:s');
        }
    
        // Nếu không tìm thấy số lượng và đơn vị thời gian, trả về ngày giờ hiện tại
        return $currentDateTime->format('Y-m-d H:i:s');
    }


    private function findViewCounts($crawler) {
        // Tìm tất cả các phần tử chứa văn bản phù hợp với biểu thức chính quy
        $node = $crawler->filterXPath('//*[contains(text(), "N/A, it has")]');
    
            $text = $node->text();
            if (preg_match('/N\/A, it has (\d+(\.\d+)?)([KkMmBb]?) views/i', $text, $matches)) {
                $number = (float) $matches[1];
                $modifier = strtoupper($matches[3]);
    
                switch ($modifier) {
                    case 'K':
                        $number *= 1000;
                        break;
                    case 'M':
                        $number *= 1000000;
                        break;
                    case 'B':
                        $number *= 1000000000;
                        break;
                }
    
                return $number;
            }
    
        return 0;
    }


    public function getChapterData($chapter_url)
    {
        try {
            // $response = CurlHelper::fetchHtmlViaProxy($chapter_url);
            $response = $this->fetchHtmlViaProxyChapterData($chapter_url);
        
                $crawler = new Crawler($response);
                $chapterContent = $crawler->filterXPath('.//div[@class="reading-content"]');
        
                $chapterImagesData = $chapterContent->filterXPath('.//div[@class="reading-content"]/div[@class="page-break no-gaps"]')->each(function (Crawler $chapterNode, $j) {
                    $pageNumber = $j;
                    $imagePath = trim($chapterNode->filterXPath('.//img')->attr('src')) ?? '';
                    
                    return [
                        'image_page' => $pageNumber,
                        'image_file' => $imagePath,
                    ];
                });

                $chapterData = [
                    'chapter_images' => $chapterImagesData,
                ];
                
                    return [
                    'data' => [
                        'item' => $chapterData,
                    ],
                ];
                
        } catch (Exception $e) {
            // return [
            //     'message' => $e->getMessage(),
            // ];
        }        
    }

    //
    //
    //
    //
    //

    protected $link, $client, $imageStorageManager;
    public function __construct($link)
    {
        $this->link = $link;
        $this->imageStorageManager = new ImageStorageManager();
    }

    //MAIN
    public function handle()
    {
        try {
        $startTime = microtime(true);
        $mangaData = $this->getMangaDetailData($this->link);
        print_r($mangaData);
        
        if (isset($mangaData['data']['item'])) {
        $mangaResponseData = $mangaData['data']['item'];

        printf("- Đang tải truyện '%s'...", $mangaResponseData['name']);

        $manga = Manga::where('slug', $mangaResponseData['slug'])->first();
        if (!$manga) {
            $info = (new Collector($mangaData))->get();
           $manga = $this->createManga($info, $mangaResponseData);
           View::create([
            'model' => 'Ophim\Core\Models\Manga', 
            'key' => $manga->id, 
            'views' => $mangaResponseData['views']
            ]);
        }

        $this->syncCategories($manga, $mangaResponseData);
        $this->syncChapters($manga, $mangaResponseData);

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        printf("\n=> Đã tải xong truyện '%s' trong %s giây\n", $mangaResponseData['name'], $executionTime);
    } 
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    }

    private function createManga($info, $mangaResponseData)
    {
        return Manga::create($info);
    }

    protected function syncCategories($manga, array $mangaResponseData)
    {
        $categories = [];
        $statusSlug  = $mangaResponseData['status'];
        $statusName  = $mangaResponseData['status_name'];

        foreach ($mangaResponseData['category'] as $categoryData) {
            $categorySlug = $categoryData['slug'];
            $categoryName = $categoryData['name'];
            if (!$categorySlug) continue;

            // Kiểm tra nếu taxonomy đã tồn tại
            $category = Taxonomy::where('slug', $categorySlug)
                                ->first();

            if (!$category) {
            $newCategory = Taxonomy::create([
                'name' => $categoryName,
                'slug' => $categorySlug,
                'type' => 'genre'
            ]);
            $categories[] = $newCategory->id;
            } else {
                $categories[] = $category->id;
            }
        }

        // Kiểm tra nếu status đã tồn tại
        $status = Taxonomy::where('slug', $statusSlug)
                        ->where('type', 'status')
                        ->first();

        if($status){
            // Thêm id của status vào mảng $categories
        $categories[] = $status->id;
        } else {
            $newStatus = Taxonomy::create([
                'name' => $statusName,
                'slug' => $statusSlug,
                'type' => 'status'
            ]);
            $categories[] = $newStatus->id;
        }
        
        // Thêm các taxonomy vào cho manga
        foreach($categories as $category) {
            Taxable::firstOrCreate([
                'taxonomy_id' => $category,
                'taxable_id' => $manga->id
            ]);
        }
    }


public function syncChapters($manga, $mangaResponseData)
{
        $existingChapters = Chapter::where('manga_id', $manga->id)->pluck('chapter_number')->all();

        $newChapters  = [];
        foreach ($mangaResponseData['chapters'] as $chapter) {
            if (!in_array($chapter['chapter_name'], $existingChapters)) {
            $newChapters[] = [
                'chapter_name' => $chapter['chapter_name'],
                'chapter_api_data' => $chapter['chapter_api_data'],
                'chapter_updated_at' => $chapter['chapter_updated_at'],
            ];
        }
        }

        if (!empty($newChapters)) {
        foreach($newChapters as $chapter){
            $chapter_number = $chapter['chapter_name'];
            $chapter_api_url = $chapter['chapter_api_data'];
            $chapter_updated_at = $chapter['chapter_updated_at'];
            $startTime = microtime(true);

            $this->createNewChapter($manga, $chapter_api_url, $chapter_number, $chapter_updated_at);
                
            $endTime = microtime(true);
            $elapsedTime = $endTime - $startTime;
            $current_date = date('Y-m-d H:i:s');
            printf("\n-- [%s] Đã thêm mới Chapter '%s' trong %.4f giây !", $current_date, $chapter_number, $elapsedTime);  
        }
    }
}

    public function createNewChapter($manga, $chapter_api_url, $chapterNumber, $chapterUpdatedAt){
        $chapterDataResponse = $this->getChapterData($chapter_api_url);

        if (!isset($chapterDataResponse['data']['item'])) {
            return;
        }

        $chapterData = $chapterDataResponse['data']['item'];
            
        $pageUrls = $chapterData['chapter_images'];
        // $createdAt = $chapterData['chapter_updated_at'];
        $createdAt = $chapterUpdatedAt;
                
        $content = array_map(function ($pageUrl) {
            return 'https://trieubui.top/api/v1/get-bp-truyenvn-image?image_url=' . $pageUrl['image_file'];
        }, $pageUrls ?? []);

        Chapter::create([
                'title' => '',
                'chapter_number' => $chapterNumber,
                'manga_id' => $manga->id,
                'content' => $content,
                'content_sv2' => $content,
                'status' => 'waiting_to_upload',
                'created_at' => $createdAt,
                'updated_at' => $createdAt
        ]);

        //cập nhật cột updated_at cho manga
        $manga->update(['updated_at' => $createdAt]);
    }


}
