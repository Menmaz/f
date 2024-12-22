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
        $info['content'] = str_replace('NetTruyen', 'Manga.TB', $info['content']);

        $data = [
            'title' => $info['name'],
            'alternative_titles' => $info['origin_name'],
            'slug' => $info['slug'],
            'description' => $info['content'],
            // 'author' => [],
            'status' => $info['status'],
            'cover' => $coverUrl,
            'created_at' => $info['createdAt'],
            // 'updated_at' => $info['updatedAt']
        ];

        return $data;
    }
}

class NettruyenCrawler
{
    protected $link, $client, $imageStorageManager;
    public function __construct($link)
    {
        $this->link = $link;
        $this->imageStorageManager = new ImageStorageManager();
    }

    //lấy dữ liệu từ trang chi tiết truyện
    public function getMangaDetailData($url)
    {
        try {
        $body = CurlHelper::fetchHtmlViaNoProxy($url);

        $crawler = new Crawler($body);

        $manga['name'] = $crawler->filterXPath('.//h1[@class="title-detail"]')->text() ?? '';
        $manga['slug'] = preg_replace('/-\d+$/', '', basename(parse_url($url, PHP_URL_PATH)));

        $otherName = $crawler->filterXPath('.//h2[@class="other-name col-xs-8"]');
        if($otherName->count() > 0){
            $manga['origin_name'] = $otherName->text() ?? "";
        } else {
            $manga['origin_name'] = "";
        }

        $categoriesNode = $crawler->filterXPath('.//li[@class="kind row"]/p[@class="col-xs-8"]/a');
        if ($categoriesNode->count() > 0) {
            $manga['category'] = $categoriesNode->each(function (Crawler $genreNode) {
                $genreName = $genreNode->text() ?? '';
                $slug = Str::slug($genreName);
                return [
                    'name' => $genreName,
                    'slug' => $slug,
                ];
            }) ?? [];
    
        } else {
            $manga['category'] = [];
        }

        $contentNode = $crawler->filterXPath('.//div[@class="detail-content"]//div[1]');
        if($contentNode->count() > 0){
            $manga['content'] = $contentNode->text() ?? '';
        } else {
            $manga['content'] = "Đang cập nhật";
        }
        

        $statusNode = $crawler->filterXPath('.//li[@class="status row"]/p[@class="col-xs-8"]');
        if($statusNode->count() > 0){
            $statusText = $statusNode->text() ?? "";
            switch ($statusText) {
                case 'Đang tiến hành':
                    $manga['status_name'] = "Đang phát hành";
                    $manga['status'] = 'ongoing';
                    break;
                case 'Hoàn Thành':
                    $manga['status_name'] = "Hoàn thành";
                    $manga['status'] = 'completed';
                    break;
                default:
                    $manga['status_name'] = "Đang phát hành";
                    $manga['status'] = 'ongoing'; 
                    break;
            }
        }
        
        $manga['thumb_url'] = 'https://dtcdnyacd.com/nettruyen/thumb/'. $manga['slug'] .'jpg'; 
        $thumbNode = $crawler->filterXPath('.//img[@class="image-thumb"]');
        if($thumbNode->count() > 0){
            $manga['thumb_url'] = $thumbNode->attr('src');
        } else {
            $manga['thumb_url'] = 'https://dtcdnyacd.com/nettruyen/thumb/'. $manga['slug'] .'jpg';
        }
        
        $authorNode = $crawler->filterXPath('.//li[@class="author row"]/p[@class="col-xs-8"]');
        if($authorNode->count() > 0){
            $manga['author'] = $authorNode->text() ?? '';
        } else {
            $manga['author'] = '';
        }
        
        $viewNode = $crawler->filterXPath('.//li[@class="row"]/p[@class="col-xs-8"]');   
        if($viewNode->count() > 0){
            $manga['views'] = $viewNode->text();
            $manga['views'] = (int)str_replace('.', '', $manga['views']);
        } else {
            $manga['views'] = 0;
        }
          
        $chaptersNode = $crawler->filterXPath('.//div[@class="list-chapter"]/nav/ul/li[@class="row "]');
        if($chaptersNode->count() > 0){
            $manga['chapters'] = $chaptersNode->each(function (Crawler $chapterNode) {
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
            });
        } else {
            $manga['chapters'] = [];
        }

        $additionalChaptersNode = $crawler->filterXPath('.//div[@class="list-chapter"]/nav/ul/li[@class="row less"]');
        if($additionalChaptersNode->count() > 0){
            $additionalChapters = $additionalChaptersNode->each(function (Crawler $chapterNode) {
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
            });
            $manga['chapters'] = array_merge($manga['chapters'], $additionalChapters);
        } else {
            $additionalChapters = [];
        }

            usort($manga['chapters'], function ($a, $b) {
                return $a['chapter_name'] <=> $b['chapter_name'];
            });
            
        $manga['createdAt'] = $manga['chapters'] ? $manga['chapters'][0]['chapter_updated_at'] : null;

        return [
            "data" => [
                'item' => $manga,
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
    
            return $chapterDateTime->format('Y-m-d H:i:s');
        }
    
        return $currentDateTime->format('Y-m-d H:i:s');
    }


    public function getChapterData($chapter_url)
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

    //MAIN
    public function handle()
    {
        try {
        $startTime = microtime(true);
        $mangaData = $this->getMangaDetailData($this->link);
        
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
        $statusSlug  = $mangaResponseData['status'];
        $statusName  = $mangaResponseData['status_name'];

        foreach ($mangaResponseData['category'] as $categoryData) {
            $categorySlug = $categoryData['slug'];
            $categoryName = $categoryData['name'];

            // Bỏ qua các thể loại '18+' và '16+'
            // if (in_array($categoryName, ['18+', '16+'])) {
            //     continue;
            // }
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
        foreach ($mangaResponseData['chapters'] as $chapter) {
            $chapter_number = $chapter['chapter_name'];
            $existingChapter = Chapter::where('manga_id', $manga->id)->where('chapter_number', $chapter_number)->first();
            if(!$existingChapter){
                $chapter_api_url = $chapter['chapter_api_data'];
                
                $startTime = microtime(true);
                $this->createNewChapter($manga, $chapter_api_url, $chapter_number);
                    
                $endTime = microtime(true);
                $elapsedTime = $endTime - $startTime;
                $current_date = date('Y-m-d H:i:s');
                printf("\n-- [%s] Đã thêm mới Chapter '%s' trong %.4f giây !", $current_date, $chapter_number, $elapsedTime); 
            }
        }
}

    public function createNewChapter($manga, $chapter_api_url, $chapterNumber){
        $chapterDataResponse = $this->getChapterData($chapter_api_url);

            if (!isset($chapterDataResponse['data']['item'])) {
                return;
            }

            $chapterData = $chapterDataResponse['data']['item'];
                $pageUrls = $chapterData['chapter_images'];
                $createdAt = $chapterData['chapter_updated_at'];
                
                $content = array_map(function ($pageUrl) {
                        $encodedUrl = base64_encode($pageUrl['image_file']);
                        return 'https://truyen.taxoakumi.xyz/api/v1/get-bp-image?image_url=' . $encodedUrl;
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
    }


}
