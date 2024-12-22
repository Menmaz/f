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

class Collector
{
    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function get(): array
    {
        $info = $this->payload['data']['item'] ?? [];

        $coverUrl = ImageHelper::uploadedMangaThumb($info['slug'], $info['thumb_url']);

        return [
            'title' => $info['name'],
            // 'origin_name' => $info['origin_name'],
            'origin_name' => implode(', ', $info['origin_name']),
            'slug' => $info['slug'],
            'description' => $info['content'],
            // 'author' => $info['author'],
            'author' => implode(', ', $info['author']),
            'status' => $info['status'],
            'cover' => $coverUrl,
            'created_at' => $info['createdAt'],
        ];
    }
}

class TruyenqqCrawler
{
    protected $link; 
    protected $imageStorageManager;
    protected $iconHref;
    public function __construct($link)
    {
        $this->link = $link;
        $this->imageStorageManager = new ImageStorageManager();
        $this->iconHref = "https://st.truyenqqviet.com/template/frontend/images/favicon.ico";
    }

    //MAIN
    public function handle()
    {
        try {
        $startTime = microtime(true);
        echo "Requesting: " . $this->link . "\n";
        $mangaData = $this->getMangaDetailData($this->link);
        // print_r($mangaData);
        // $chapterData = $this->getChapterData("https://truyenqqviet.com/truyen-tranh/su-quyen-ru-cua-2-5d-8124-chap-112.html");
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
        }

        $this->syncCategories($manga, $mangaResponseData);
        $this->syncChapters($manga, $mangaResponseData);

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        printf("\n=> Đã tải xong truyện '%s' trong %s giây\n", $mangaResponseData['name'], $executionTime);
    } else {
        echo "=>Truyện đã được bỏ qua hoặc dữ liệu không hợp lệ !.\n";
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
            $categorySlug = $categoryData['slug'];
            if (!$categorySlug) continue;

            // Kiểm tra nếu taxonomy đã tồn tại
            $category = Taxonomy::where('slug', $categorySlug)
                                ->first();

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

        if($status){
            // Thêm id của status vào mảng $categories
        $categories[] = $status->id;
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
            $newChapters = array_filter($mangaResponseData['chapters'], function ($chapter) use ($existingChapters) {
                return !in_array($chapter['chapter_name'], $existingChapters);
            });

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
            
        // $chapterNumber = $chapterData['chapter_name'];
        $pageUrls = $chapterData['chapter_images'];
        $createdAt = $chapterData['chapter_updated_at'];
                
        $content = array_map(function ($pageUrl) {
            return 'https://truyen.taxoakumi.xyz/api/v1/get-bp-truyenqq-image?image_url=' . $pageUrl['image_file'];
                // return 'http://localhost:8081/10truyenAPIs/public/api/v1/get-bp-truyenqq-image?image_url=' . $pageUrl['image_file'];
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

    //
    //
    //

    //lấy dữ liệu từ trang chi tiết truyện
    public function getMangaDetailData($url)
    {
        try {
        $body = CurlHelper::fetchHtmlViaProxy($url, $this->iconHref);
        $crawler = new Crawler($body);
        $manga = [];

        $manga['category'] = $crawler->filterXPath('.//ul[@class="list01"]/li[@class="li03"]')->each(function (Crawler $genreNode) {
            $genreName = $genreNode->filterXPath('.//a')->text() ?? '';
            $slug = Str::slug($genreName);
            return [
                'name' => $genreName,
                'slug' => $slug,
            ];
        }) ?? [];

            // Kiểm tra nếu category chứa manwhua, manhwa, webtoon thì không cào truyện đó
        $skipManga = false;
        foreach ($manga['category'] as $category) {
            if (in_array($category['name'], ['Manhua', 'Manhwa', 'Webtoon'])) {
                $skipManga = true;
                break;
            }
        }

        if ($skipManga) {
            return null;
        }

            $manga['name'] = $crawler->filterXPath('.//h1[@itemprop="name"]')->text() ?? '';
            $manga['slug'] = preg_replace('/-\d+$/', '', basename(parse_url($url, PHP_URL_PATH)));
            $manga['origin_name'] = [];
            if ($crawler->filterXPath('.//h2[@class="other-name col-xs-9"]')->count() > 0) {
                $text = $crawler->filterXPath('.//h2[@class="other-name col-xs-9"]')->text();
                $manga['origin_name'] = array_map('trim', explode(';', $text));
            }
            $manga['content'] = $crawler->filterXPath('.//div[@class="story-detail-info detail-content"]')->text() ?? '';
            $manga['status'] = $crawler->filterXPath('.//li[@class="status row"]/p[@class="col-xs-9"]')->text() ?? '';
            $manga['thumb_url'] = $crawler->filterXPath('.//div[@class="book_avatar"]/img')->attr('src') ?? '';
            $manga['author'] = $crawler->filterXPath('.//li[@class="author row"]/p[@class="col-xs-9"]/a')->each(function (Crawler $originNode) {
                return $originNode->text();
            }) ?? '';

                $manga['chapters'] = $crawler->filterXPath('.//div[@class="works-chapter-list"]/div[@class="works-chapter-item"]')->each(function (Crawler $chapterNode) {
                $chapterNameNode = $chapterNode->filterXPath('.//div[@class="col-md-10 col-sm-10 col-xs-8 name-chap"]/a');
                $chapterTimeNode = $chapterNode->filterXPath('.//div[@class="col-md-2 col-sm-2 col-xs-4 time-chap"]');
            
                $chapterName = $chapterNameNode->text() ?? '';
                if (preg_match('/\d+(\.\d+)?/', $chapterName, $matches)) {
                    $chapterNumber = isset($matches[0]) ? $matches[0] : null;
                    $chapterUrl = "https://truyenqqviet.com" . $chapterNameNode->attr('href') ?? '';
                    $chapterTime = $chapterTimeNode->text() ?? '';
            
                    return [
                        'filename' => '',
                        'chapter_name' => $chapterNumber,
                        'chapter_api_data' => $chapterUrl,
                        'chapter_updated_at' => trim($chapterTime),
                    ];
                }
            }) ?? [];

            usort($manga['chapters'], function ($a, $b) {
                return $a['chapter_name'] <=> $b['chapter_name'];
            });

            $manga['createdAt'] = !empty($manga['chapters']) ? Carbon::createFromFormat('d/m/Y', $manga['chapters'][0]['chapter_updated_at'])->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s');

            return [
                "data" => [
                    'item' => $manga,
                ]
                ];
    } catch (Exception $e) {
        // return [
        //     'message' => $e->getMessage()
        // ];
    }
    }

    public function getChapterData($chapter_url)
    {
        try {
            $response = CurlHelper::fetchHtmlViaProxy($chapter_url, $this->iconHref);
        
                $crawler = new Crawler($response);
        
                $chapterTitle = $crawler->filterXPath('.//h1[@class="detail-title txt-primary"]')->text() ?? '';
                preg_match('/Chapter (\d+(\.\d+)?)/', $chapterTitle, $matches);
                $chapterNumber = $matches[1];
                // Trích xuất ngày cập nhật chapter
                $chapterUpdatedAtString = $crawler->filterXPath('.//time')->attr('datetime') ?? '';
                $chapterUpdatedAt = Carbon::parse($chapterUpdatedAtString)->format('Y-m-d H:i:s');
                $chapterContent = $crawler->filterXPath('.//div[@class="chapter_content"]');
        
                $chapterImagesData = $chapterContent->filterXPath('.//div[@class="page-chapter"]')->each(function (Crawler $chapterNode, $j) {
                    $imagePage = $chapterNode->attr('id') ?? '';
                    $pageNumber = (int) str_replace('page_', '', $imagePage);
                    $imagePath = $chapterNode->filterXPath('.//img')->attr('src') ?? '';
                    
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

}
