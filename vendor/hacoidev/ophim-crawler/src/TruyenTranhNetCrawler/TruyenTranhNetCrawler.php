<?php

namespace Ophim\Crawler\OphimCrawler\TruyenTranhNetCrawler;

use App\Helpers\CurlHelper;
use App\Helpers\ImageHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
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

        $data = [
            'title' => $info['name'],
            'origin_name' => $info['origin_name'],
            'slug' => $info['slug'],
            'description' => $info['content'],
            'author' => $info['author'],
            'status' => $info['status'],
            'cover' => $info['thumb_url'],
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $data;
    }
}

class TruyenTranhNetCrawler
{
    
    //lấy dữ liệu từ trang chi tiết truyện
    public function getMangaDetailData($url)
    {
        try {
        // $body = CurlHelper::fetchHtmlViaNoProxy($url);
        // $crawler = new Crawler($body);
        $url = $this->link;
        exec("cd puppeteer-api && node index.js $url", $output, $return_var);
        if ($return_var !== 0) {
            // Xử lý lỗi nếu có
            printf('Failed to run Puppeteer script');
        }

        $html = implode("\n", $output);
        $crawler = new Crawler($html);
        $manga = [];

        $manga['name'] = str_replace(' - Truyện Tranh', '', $crawler->filterXPath('.//h1')->text() ?? '');
        $manga['slug'] = basename(parse_url($url, PHP_URL_PATH));

        // Extracting tên khác
        $manga['origin_name'] = '';

        $contentNode = $crawler->filterXPath('.//div[contains(@class, "content-posts")]');
        if($contentNode->count() > 0){
             $manga['content'] = $contentNode->text() ?? '';
        } else {
            $manga['content'] = '';
        }

        $categoryNamesNode = $crawler->filterXPath('.//div[h4[contains(text(),"Thể Loại")]]');
        if ($categoryNamesNode->count() > 0) {
            $manga['category'] = $categoryNamesNode->filterXPath('.//ul/li')->each(function (Crawler $node) {
                $genreName = $node->filter('a')->text() ?? '';
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

        $statusNode = $crawler->filterXPath('.//span[small[contains(text(),"Trạng thái")]]');
        if ($statusNode->count() > 0) {
            $statusText = $statusNode->filter('strong')->text(); 
            if($statusText == 'Đang ra'){
                 $manga['status_name'] = "Đang phát hành";
                $manga['status'] = 'ongoing';
            } else if($statusText == 'Hoàn thành') {
                 $manga['status_name'] = "Hoàn thành";
                $manga['status'] = 'completed';
            }
        } else {
            $manga['status_name'] = "Hoàn thành";
            $manga['status'] = 'ongoing';
        }

        $manga['thumb_url'] = $crawler->filterXPath('//img[@itemprop="image"]')->attr('src') ?? '';

        $authorNode = $crawler->filterXPath('.//div[@itemprop="author"]');
        if ($authorNode->count() > 0) {
            $manga['author'] = $authorNode->filterXPath('.//span[@itemprop="name"]')->text();
        } else {
            $manga['author'] = 'Đang cập nhật';
        }

        $viewNode = $crawler->filterXPath('.//span[small[contains(text(),"Lượt xem")]]');
        if ($viewNode->count() > 0) {
            $viewText = $viewNode->filter('strong')->text();
            $manga['views'] = $this->findViewCounts($viewText);
        } else {
            $manga['views'] = 0;
        }

        $chaptersHtml = $crawler->filterXPath('.//ul[@id="list-chapter-comic"]')->html();
        $chapterCrawler = new Crawler($chaptersHtml);
        $manga['chapters'] = $chapterCrawler->filterXPath('//li')->each(function (Crawler $node) {
            $chapterText = $node->filter('a')->text() ?? '';
            if (preg_match('/Chapter (\d+(\.\d+)?)/', $chapterText, $matches)) {
                $chapterNumber = floatval($matches[1]) ?? null;
                $chapterUrl = $node->filter('a')->attr('href') ?? '';
                return [
                    'chapter_name' => $chapterNumber,
                    'chapter_api_data' => "https://truyentranh.net.vn" . $chapterUrl,
                ];
            }
        });

        usort($manga['chapters'], function ($a, $b) {
            return $a['chapter_name'] <=> $b['chapter_name'];
        });
            
            return [
            "data" => [
                'item' => $manga,
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage()
        ];
    }
    }

   
    private function findViewCounts($text) {
        if (preg_match('/(\d+(\.\d+)?)([KkMmBb]?)/', $text, $matches)) {
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
            $response = CurlHelper::fetchHtmlViaNoProxy($chapter_url);
        
                $crawler = new Crawler($response);
                $timeString = $crawler->filter('i')->text();

                // Sử dụng biểu thức chính quy để lấy phần thời gian từ nội dung text
                if (preg_match('/\[Đăng lúc:\s*(\d{4}-\d{2}-\d{2})\]/', $timeString, $matches)) {
                    $dateString = $matches[1];
                    $timestamp = strtotime($dateString);
                    $datetime = date('Y-m-d H:i:s', $timestamp);
                } 
        
                $chapterImagesData = $crawler->filterXPath('.//div[@class="xl:max-w-[990px] lg:max-w-[940px] md:max-w-[760px] w-full mx-auto"]/img')->each(function (Crawler $chapterNode, $j) {
                    $pageNumber = $j;
                    $imagePath = trim($chapterNode->attr('src')) ?? '';
                    
                    return [
                        'image_page' => $pageNumber,
                        'image_file' => $imagePath,
                    ];
                });

                $chapterData = [
                    'chapter_images' => $chapterImagesData,
                    'chapter_created_at' => $datetime
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
            ];
        }
        }

        if (!empty($newChapters)) {
        foreach($newChapters as $chapter){
            $chapter_number = $chapter['chapter_name'];
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
        $createdAt = $chapterData['chapter_created_at'];
                
        $content = array_map(function ($pageUrl) {
            return 'https://trieubui.top/get-bp-image?image_url=' . $pageUrl['image_file'] . '&referrer=https://truyenvn.me/';
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
