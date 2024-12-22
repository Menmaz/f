<?php

namespace Ophim\Crawler\OphimCrawler\Manga18clubCrawler;

use App\Helpers\CurlHelper;
use Carbon\Carbon;
use Exception;
use Ophim\Core\Models\Taxable;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;
use Symfony\Component\DomCrawler\Crawler;
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
            'alternative_titles' => $info['origin_name'],
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

class Manga18clubCrawler
{
    //lấy dữ liệu từ trang chi tiết truyện
    public function getMangaDetailData($url)
    {
        try {
        $body = CurlHelper::fetchHtmlViaProxy($url);
        $crawler = new Crawler($body);
        $manga = [];

        $manga['name'] = $crawler->filterXPath('.//div[@class="post-title"]')->text() ?? '';

        // Extracting tên khác
        $alternateNamesNode = $crawler->filterXPath('//div[@class="post-content_item"]//div[@class="summary-heading"]/h5[contains(text(), "Tên khác")]');
        if ($alternateNamesNode->count() > 0) {
            $manga['origin_name'] = trim($alternateNamesNode->parents()->filter('.summary-content')->text());
        } else {
            $manga['origin_name'] = 'Updating';
        }

        $contentNode = $crawler->filterXPath('.//div[@class="description-summary"]');
        if($contentNode->count() > 0){
             $manga['content'] = $contentNode->text() ?? '';
        } else {
            $manga['content'] = 'Updating';
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

        $statusText = $crawler->filterXPath('.//div[@class="post-status"]/div[@class="post-content_item"]/div[@class="summary-content"]')->text() ?? '';
        if($statusText == ' OnGoing '){
            $manga['status_name'] =  "Ongoing";
            $manga['status'] = 'ongoing';
        } else if($statusText == ' Completed ') {
            $manga['status_name'] =  "終了した";
            $manga['status'] = 'completed';
        } else {
            $manga['status_name'] =  "Ongoing";
            $manga['status'] = 'ongoing';
        }
        

        $manga['thumb_url'] = $crawler->filterXPath('.//div[@class="summary_image"]/a/img')->attr('data-src') ?? '';

        $slug = Str::slug($manga['name']);
        $parts = explode('-', $slug);
        $uniqueParts = array_unique($parts);
        $manga['slug'] = implode('-', $uniqueParts);
        
        $manga['author'] = 'Updating';

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
        $currentDateTime = Carbon::now();
        
        // Kiểm tra xem chuỗi có phải là một ngày cụ thể không (định dạng dd/mm/yyyy)
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $relativeTime, $dateMatches)) {
            $day = intval($dateMatches[1]);
            $month = intval($dateMatches[2]);
            $year = intval($dateMatches[3]);
            $specificDate = Carbon::create($year, $month, $day);
            
            return $specificDate->format('Y-m-d H:i:s');
        }

        if (preg_match('/([A-Za-z]+) (\d{1,2}), (\d{4})/', $relativeTime, $dateMatches)) {
            $monthName = $dateMatches[1];
            $day = intval($dateMatches[2]);
            $year = intval($dateMatches[3]);
            
            // Chuyển đổi tên tháng thành số tháng
            $month = Carbon::parse($monthName)->month;
    
            $specificDate = Carbon::create($year, $month, $day);
            return $specificDate->format('Y-m-d H:i:s');
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
            $response = CurlHelper::fetchHtmlViaProxy($chapter_url);
        
                $crawler = new Crawler($response);        
                $chapterImagesData = $crawler->filterXPath('.//div[@class="page-break no-gaps"]')->each(function (Crawler $chapterNode, $j) {
                    $pageNumber = $j;
                    $imagePath = trim($chapterNode->filterXPath('.//img')->attr('data-src')) ?? '';
                    
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

        }        
    }


    protected $link, $client;
    public function __construct($link)
    {
        $this->link = $link;
    }

    //MAIN
    public function handle()
    {
        try {
        $startTime = microtime(true);
        // $mangaData = $this->getMangaDetailData($this->link);
        // print_r($mangaData);

        $mangaData = $this->getMangaDetailData("https://manga18.club/manhwa/naughty-positions");
        print_r($mangaData);

        // $chapterData = $this->getChapterData("https://yaoiscan.com/read/shisen-beya/chapter-1/");
        // print_r($chapterData);
        
    //     if (isset($mangaData['data']['item'])) {
    //     $mangaResponseData = $mangaData['data']['item'];

    //     printf("- Đang tải truyện '%s'...", $mangaResponseData['name']);

    //     $manga = Manga::where('slug', $mangaResponseData['slug'])->first();

    //     if (!$manga) {
    //        $info = (new Collector($mangaData))->get();
    //        $manga = $this->createManga($info, $mangaResponseData);
    //        View::create([
    //         'model' => 'Ophim\Core\Models\Manga', 
    //         'key' => $manga->id, 
    //         'views' => $mangaResponseData['views']
    //         ]);
    //     }

    //     $this->syncCategories($manga, $mangaResponseData);
    //     $this->syncChapters($manga, $mangaResponseData);

    //     $endTime = microtime(true);
    //     $executionTime = round($endTime - $startTime, 2);
    //     printf("\n=> Đã tải xong truyện '%s' trong %s giây\n", $mangaResponseData['name'], $executionTime);
    // } 
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
        $statusName  = $mangaResponseData['status_name'];
        $statusSlug  = $mangaResponseData['status'];

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
        $createdAt = $chapterUpdatedAt;
                
        $content = array_map(function ($pageUrl) {
                return $pageUrl['image_file'];
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
