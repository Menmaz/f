<?php

namespace Ophim\Crawler\OphimCrawler;

use App\Helpers\CurlHelper;
use App\Helpers\ImageHelper;
use Carbon\Carbon;
use Exception;
use Ophim\Core\Models\Taxable;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
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

        $coverUrl = ImageHelper::uploadedMangaThumb($info['slug'], $info['thumb_url']);
        $info['content'] = str_replace('NetTruyen', '10Truyen', $info['content']);

        $data = [
            'title' => $info['name'],
            'origin_name' => $info['origin_name'],
            'slug' => $info['slug'],
            'description' => $info['content'],
            'author' => implode(', ', $info['author']),
            'status' => $info['status'],
            'cover' => $coverUrl,
            'created_at' => $info['createdAt'],
        ];

        return $data;
    }
}

class NettruyenRecentChaptersCrawler
{
    //lấy dữ liệu từ trang chi tiết truyện
    public function getMangaDetailData($url)
    {
        try {
        $body = CurlHelper::fetchHtmlViaProxy($url);

        $crawler = new Crawler($body);
        $manga = [];
        $mangaInfo = $crawler->filterXPath('.//article[contains(@id, "item-detail")]')
        ->each(function (Crawler $node) use ($url, $manga){
            $manga['name'] = $node->filterXPath('.//h1[@class="title-detail"]')->text() ?? '';
            $manga['slug'] = preg_replace('/-\d+$/', '', basename(parse_url($url, PHP_URL_PATH)));

            $manga['origin_name'] = $node->filterXPath('.//h2[@class="other-name col-xs-8"]')->count() > 0 
            ? $node->filterXPath('.//h2[@class="other-name col-xs-8"]')->text() 
            : "";

            $manga['content'] = $node->filterXPath('.//div[@class="detail-content"]//div[1]')->text() ?? '';
            $manga['status'] = $node->filterXPath('.//li[@class="status row"]/p[@class="col-xs-8"]')->text() ?? '';
            $manga['thumb_url'] = $node->filterXPath('.//div[@class="col-xs-4 col-image"]/img')->attr('src') ?? '';

            $manga['author'] = $node->filterXPath('.//li[@class="author row"]/p[@class="col-xs-8"]')->each(function (Crawler $originNode) {
                return $originNode->text();
            }) ?? '';

            $manga['category'] = $node->filterXPath('.//li[@class="kind row"]/p[@class="col-xs-8"]/a')->each(function (Crawler $genreNode) {
                $genreName = $genreNode->text() ?? '';
                $slug = Str::slug($genreName);
                return [
                    'name' => $genreName,
                    'slug' => $slug,
                ];
            }) ?? [];
            
            $manga['views'] = $node->filterXPath('.//li[@class="row"]/p[@class="col-xs-8"]')->text();
            $manga['views'] = (int)str_replace('.', '', $manga['views']);
            
            $manga['chapters'] = $node->filterXPath('.//div[@class="list-chapter"]/nav/ul/li[@class="row "]')->each(function (Crawler $chapterNode) {
                $chapterName = $chapterNode->filterXPath('.//div[@class="col-xs-5 chapter"]/a')->text() ?? '';
                preg_match('/\d+(\.\d+)?/', $chapterName, $matches);
                $chapterNumber = isset($matches[0]) ? $matches[0] : null;
                $chapterUrl = $chapterNode->filterXPath('.//div[@class="col-xs-5 chapter"]/a')->attr('href') ?? '';
                $chapterTime = $chapterNode->filterXPath('.//div[@class="col-xs-4 no-wrap small text-center"]')->text() ?? '';
                $chapterTime = $this->convertRelativeTimeToDateTime($chapterTime);

                return [
                    'filename' => '',
                    'chapter_name' => $chapterNumber,
                    'chapter_api_data' => $chapterUrl,
                    'chapter_updated_at' => $chapterTime,
                ];
            }) ?? [];

            $additionalChapters = $node->filterXPath('.//div[@class="list-chapter"]/nav/ul/li[@class="row less"]')->each(function (Crawler $chapterNode) {
                $chapterName = $chapterNode->filterXPath('.//div[@class="col-xs-5 chapter"]/a')->text() ?? '';
                preg_match('/\d+(\.\d+)?/', $chapterName, $matches);
                $chapterNumber = isset($matches[0]) ? $matches[0] : null;
                $chapterUrl = $chapterNode->filterXPath('.//div[@class="col-xs-5 chapter"]/a')->attr('href') ?? '';
                $chapterTime = $chapterNode->filterXPath('.//div[@class="col-xs-4 no-wrap small text-center"]')->text() ?? '';
                $chapterTime = $this->convertRelativeTimeToDateTime($chapterTime);

                return [
                    'filename' => '',
                    'chapter_name' => $chapterNumber,
                    'chapter_api_data' => $chapterUrl,
                    'chapter_updated_at' => $chapterTime,
                ];
            }) ?? [];

            $manga['chapters'] = array_merge($manga['chapters'], $additionalChapters);

            usort($manga['chapters'], function ($a, $b) {
                return $a['chapter_name'] <=> $b['chapter_name'];
            });
            
            $manga['createdAt'] = $manga['chapters'] ? $manga['chapters'][0]['chapter_updated_at'] : null;
            
            return $manga;
        });

        $mangaInfo = count($mangaInfo) > 0 ? $mangaInfo[0] : null;
        
        return [
            "data" => [
                'item' => $mangaInfo,
            ]
        ];
    } catch (Exception $e) {
        return [
            'message' => $e->getMessage()
        ];
    }
    }


    //chuyển đổi thời gian
    protected function convertRelativeTimeToDateTime($relativeTime) {
        // Lấy ngày giờ hiện tại
        $currentDateTime = Carbon::now();
    
        // Phân tích chuỗi để lấy số lượng và đơn vị thời gian
        preg_match('/(\d+) (phút|giờ|ngày|tháng|năm) trước/', $relativeTime, $matches);
    
        if (!empty($matches[1]) && !empty($matches[2])) {
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


    public function getChapterData($chapter_url, $retries = 10, $delay = 1)
    {
        try {
            $response = CurlHelper::fetchHtmlViaProxy($chapter_url);
        
                $crawler = new Crawler($response);
                $chapterInfo = $crawler->filterXPath('.//div[@class="reading"]/div[@class="container"]/div[@class="top"]');

                // $chapterTitle = $crawler->filterXPath('.//a')->text() ?? '';
                $chapterNumber = $chapterInfo->filter('ul.breadcrumb > li')->eq(3)->filter('a > span')->text();
                preg_match('/(Chapter|Chương) (\d+(\.\d+)?)/', $chapterNumber, $chapterMatches);
                $chapterNumber = isset($chapterMatches[2]) ? $chapterMatches[2] : null;

                $chapterUpdatedAt = $chapterInfo->filterXPath('.//i')->text();
                preg_match('/\[(?:Cập nhật lúc: )?(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $chapterUpdatedAt, $timeMatches);
                $chapterUpdatedAt = !empty($timeMatches[1]) ? $timeMatches[1] : null;

                $chapterContent = $crawler->filterXPath('.//div[@class="reading-detail box_doc"]');
        
                $chapterImagesData = $chapterContent->filterXPath('.//div[@class="page-chapter"]')->each(function (Crawler $chapterNode, $j) {
                    $pageNumber = $j;
                    $imagePath = $chapterNode->filterXPath('.//img')->attr('data-src') ?? '';
                    
                    return [
                        'image_page' => $pageNumber,
                        'image_file' => $imagePath,
                    ];
                });

                $chapterData = [
                    'chapter_name' => $chapterNumber,
                    'chapter_updated_at' => $chapterUpdatedAt,
                    'chapter_images' => $chapterImagesData,
                ];
        
                return [
                    'data' => [
                        'item' => $chapterData,
                    ],
                ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
            ];
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
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            ],
            'pool_size' => 10,
            'http_version' => '2.0',
            'connect_timeout' => 5, // Thời gian chờ kết nối (giây)
            'timeout' => 30, // Thời gian chờ yêu cầu (giây)
            'read_timeout' => 30, // Thời gian chờ đọc dữ liệu (giây)
        ]);
        $this->imageStorageManager = new ImageStorageManager();
    }

    //MAIN
    public function handle()
    {
        try {
        $startTime = microtime(true);
        $mangaData = $this->getMangaDetailData($this->link);
        // print_r($mangaData);

        // $chapterData = $this->getChapterData("https://nettruyencc.com/truyen-tranh/co-y-chiem-doat/chapter-37/37");
        // print_r($chapterData);
        
        if (isset($mangaData['data']['item'])) {
        $mangaResponseData = $mangaData['data']['item'];

        printf("- Đang tải truyện '%s'...", $mangaResponseData['name']);
        // print_r($mangaResponseData);

        // $this->syncChapters(null, $mangaResponseData);

        $manga = Manga::where('slug', $mangaResponseData['slug'])->first();

        $info = (new Collector($mangaData))->get();

        if (!$manga) {
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
    } else {
        echo "Payload does not contain expected data.\n";
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
        $statusName  = $mangaResponseData['status'];

        foreach ($mangaResponseData['category'] as $categoryData) {
            $categoryName = $categoryData['name'];
            $categorySlug = $categoryData['slug'];
            if (!$categorySlug) continue;

            // Kiểm tra nếu taxonomy đã tồn tại
            $category = Taxonomy::where('slug', $categorySlug)
                                ->first();

            if (!$category) {
                $status = Taxonomy::create([
                    'name' => trim($categoryName),
                    'slug' => trim($categorySlug),
                    'type' => 'genre'
                ]);
            }

            if ($category) {
            // Thêm id của category vào mảng $categories
            $categories[] = $category->id;
            }
        }

        // Xử lý status
        if ($statusName === 'Đang Cập Nhật') {
            $statusSlug = 'ongoing';
        } elseif ($statusName === 'Hoàn Thành') {
            $statusSlug = 'completed';
        } else {
            $statusSlug = 'ongoing';
        }

        // Kiểm tra nếu status đã tồn tại
        $status = Taxonomy::where('slug', $statusSlug)
                        ->where('type', 'status')
                        ->first();

        if (!$status) {
            $status = Taxonomy::where('name', trim($statusName))
                            ->where('type', 'status')
                            ->first();

            if (!$status) {
                // Tạo mới taxonomy nếu chưa tồn tại
                $status = Taxonomy::create([
                    'name' => trim($statusName),
                    'slug' => trim($statusSlug),
                    'type' => 'status'
                ]);
            }
        }

        // Thêm id của status vào mảng $categories
        $categories[] = $status->id;

        // Thêm các taxonomy vào cho manga
        foreach($categories as $category) {
            Taxable::firstOrCreate([
                'taxonomy_id' => $category,
                'taxable_id' => $manga->id
            ]);
        }
    }


protected function syncChapters($manga, $mangaResponseData)
{
        $existingChapters = Chapter::where('manga_id', $manga->id)->pluck('chapter_number')->all();

        $newChapters  = [];
        foreach ($mangaResponseData['chapters'] as $chapter) {
            if (!in_array($chapter['chapter_name'], $existingChapters)) {
            $newChapters[] = [
                'chapter_api_data' => $chapter['chapter_api_data'],
            ];
        }
        }

        if (!empty($newChapters)) {
        foreach($newChapters as $chapter){
            $chapter_api_url = $chapter['chapter_api_data'];
            
            $startTime = microtime(true);
            $chapterDataResponse = $this->getChapterData($chapter_api_url);

            if (!isset($chapterDataResponse['data']['item'])) {
                continue;
            }

            $chapterData = $chapterDataResponse['data']['item'];
            
                $chapterNumber = $chapterData['chapter_name'];
                $pageUrls = $chapterData['chapter_images'];
                $createdAt = $chapterData['chapter_updated_at'];
                
                $content = array_map(function ($pageUrl) {
                        return 'https://truyen.taxoakumi.xyz/api/v1/get-bp-image?image_url=' . $pageUrl['image_file'];
                        // return 'http://localhost:8081/10truyenAPIs/public/api/v1/get-bp-image?image_url=' . $pageUrl['image_file'];
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

                unset($chapterData, $pageUrls, $content);
                
                
                $endTime = microtime(true);
                $elapsedTime = $endTime - $startTime;
                $current_date = date('Y-m-d H:i:s');
                printf("\n-- [%s] Đã thêm mới Chapter '%s' trong %.4f giây !", $current_date, $chapterNumber, $elapsedTime);  
        }
    }
}


}
