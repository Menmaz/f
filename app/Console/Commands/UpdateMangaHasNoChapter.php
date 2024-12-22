<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ophim\Core\Models\Manga;
use Ophim\Crawler\OphimCrawler\ChapterCrawler;
use GuzzleHttp\Client;
use Ophim\Core\Models\Chapter;

class UpdateMangaHasNoChapter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otruyen:update-manga-has-no-chapter:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawler manga has no chapter command';

    protected $logger;
    protected $client;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->client = new Client([
            'timeout' => 30, // Timeout cho tất cả các yêu cầu HTTP
            'connect_timeout' => 30, // Timeout cho quá trình kết nối
            'verify' => false, // Tắt xác thực SSL (chỉ dùng cho môi trường phát triển)
            'max_connections' => 5, // Số lượng kết nối tối đa
            'keep_alive' => true, // Tái sử dụng kết nối
        ]);
        
    }

    public function handle()
    {
        // Lấy thời điểm bắt đầu thực hiện command
        $start_time = microtime(true);

        Manga::doesntHave('chapters')->chunk(100, function ($mangasChunk) {
            foreach ($mangasChunk as $key => $manga) {
                $response = $this->client->request('GET', "https://otruyenapi.com/v1/api/truyen-tranh/" . $manga->slug);
                $statusCode = $response->getStatusCode();
        
                if ($statusCode === 404) {
                    // Nếu nhận được mã lỗi 404, tiếp tục vòng lặp với mục tiếp theo
                    continue;
                }
                
                $body = $response->getBody()->getContents();
                $payload = json_decode($body, true);
                $mangaResponseData = $payload['data']['item'];
                printf("%d. Đang upload chapters cho truyện '%s'", $key, $manga->title);
                $this->syncChapters($manga, $mangaResponseData);
            }
        });

        printf("Finish Crawler Chapters");

        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 2);
        printf("Command execution time: %s seconds", $execution_time);

        return 0;
    }

    protected function syncChapters($manga, $mangaResponseData)
{
    printf("- Bắt đầu tải chapter cho truyện '%s'...", $mangaResponseData['name']);
    $startTime = microtime(true);

    $client = $this->client;

    // Chỉ định số lượng yêu cầu đồng thời tối đa
    $concurrency = 3; // Số lượng yêu cầu đồng thời tối đa

    $requests = function () use ($client, $mangaResponseData) {
        foreach ($mangaResponseData['chapters'] as $server) {
            foreach ($server['server_data'] as $chapter) {
                $chapter_api_url = $chapter['chapter_api_data'];
                yield function () use ($client, $chapter_api_url) {
                    return $client->getAsync($chapter_api_url);
                };
            }
        }
    };

    $pool = new \GuzzleHttp\Pool($client, $requests(), [
        'concurrency' => $concurrency,
        'fulfilled' => function ($response, $index) use ($manga) {
            // Xử lý phản hồi thành công
            if ($response->getStatusCode() === 200) {
            $chapter_data = json_decode($response->getBody());
            $chapter_detail_data = $chapter_data->data->item;
            $chapter_number = floatval($chapter_detail_data->chapter_name);
            $image_chapter_domain = $chapter_data->data->domain_cdn;

            // Lấy nội dung hình ảnh của chương
            $content = array_map(function ($chapter_image) use ($image_chapter_domain, $chapter_detail_data) {
                return $image_chapter_domain . '/' . $chapter_detail_data->chapter_path . '/' . $chapter_image->image_file;
            }, $chapter_detail_data->chapter_image ?? []);

            // Tạo hoặc cập nhật chương trong cơ sở dữ liệu
            $chapterInDB = Chapter::where('manga_id', $manga->id)->where('chapter_number', $chapter_number)->first();
            if ($chapterInDB) {
                printf("Chapter {$chapter_number} đã được tìm thấy trong truyện '{$manga->slug}'.");
                if($chapterInDB->content === null){
                    $chapterInDB->update([
                        'content' => $content,
                        'content_sv2' => $content,
                        'status' => 'waiting_to_upload'
                    ]);
                }
            } else {
                printf("Đang tạo mới chapter của truyện: {$manga->slug}, chapter_number: {$chapter_number}");
                Chapter::create([
                    'title' => $chapter_detail_data->chapter_title ?? '',
                    'chapter_number' => $chapter_number,
                    'manga_id' => $manga->id,
                    'content' => $content,
                    'content_sv2' => $content,
                    'status' => 'waiting_to_upload'
                ]);
            }
        }
        },
        'rejected' => function ($reason, $index) {
            // Xử lý khi bị từ chối hoặc lỗi
            printf("Error fetching chapter data: " . $reason);
        },
    ]);

    $promise = $pool->promise();
    $promise->wait(); // Đợi tất cả các yêu cầu hoàn thành
     
    $promise = null;
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    printf("- Đã tải xong chapter cho truyện '%s' trong %s giây.", $mangaResponseData['name'], $executionTime);
}
}
