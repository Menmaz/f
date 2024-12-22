<?php

namespace Ophim\Crawler\OphimCrawler\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Ophim\Crawler\OphimCrawler\Crawler;
use Ophim\Crawler\OphimCrawler\Option;
use Ophim\Crawler\OphimCrawler\Controllers;

class CrawlerScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ophim:plugins:ophim-crawler:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawler manga schedule command';

    protected $logger;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->logger = Log::channel('ophim-crawler');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    //otruyen version
    // public function handle()
    // {
    //     // Lấy thời điểm bắt đầu thực hiện command
    //     $start_time = microtime(true);
    
    //     if(!$this->checkCrawlerScheduleEnable()) return 0;
    //     $link = sprintf('%s/danh-sach/truyen-moi', Option::get('domain'));
    //     $this->logger->notice($link);
    //     $data = collect();
    //     $page_from = Option::get('crawler_schedule_page_from', 1);
    //     $page_to = Option::get('crawler_schedule_page_to', 3);

    //     $this->logger->notice(sprintf("Crawler Page (FROM: %d | TO: %d)",  $page_from, $page_to));
    //     printf("Tải truyện theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $page_from, $page_to);
    //     for ($i = $page_from; $i <= $page_to; $i++) {
    //         if(!$this->checkCrawlerScheduleEnable()) {
    //             $this->logger->notice(sprintf("Stop Crawler Page"));
    //             return 0;
    //         }
    //         $response = json_decode(Http::timeout(30)->get($link, [
    //             'page' => $i
    //         ]), true);
    //         if ($response['status'] && count($response['data']['items'])) {
    //             $data->push(...$response['data']['items']);
    //         }
    //     }
    //     $mangas = $data->shuffle();
    //     $count_mangas = count($mangas);
    //     $this->logger->notice(sprintf("Bắt đầu tải truyện (TỔNG: %d)",  $count_mangas));
    //     printf("Bắt đầu tải truyện (TỔNG: %d)\n", $count_mangas);
    //     $count_error = 0;
    //     foreach ($mangas as $key => $manga) {
    //         try {
    //             if(!$this->checkCrawlerScheduleEnable()) {
    //                 $this->logger->notice(sprintf("Dừng tải truyện (TỔNG: %d | ĐÃ XONG: %d | LỖI: %d)", $count_mangas, $key, $count_error));
    //                 printf("Dừng tải truyện (TỔNG: %d | ĐÃ TẢI: %d | LỖI: %d)\n", $count_mangas, $key, $count_error);
    //                 return 0;
    //             }
    //                 $link = sprintf('%s/truyen-tranh/%s', Option::get('domain'), $manga['slug']);
    //             $this->logger->notice(sprintf("%d. Bắt đầu tải truyện %s", $key + 1, $manga['name']));
    //             printf("%d. Bắt đầu tải truyện %s\n", $key + 1, $manga['name']);
    //             $crawler = (new Crawler(
    //                 $link,
    //                 Option::get('crawler_schedule_fields', Option::getAllOptions()['crawler_schedule_fields']['default']),
    //                 Option::get('crawler_schedule_excludedCategories', []),
    //                 Option::get('crawler_schedule_excludedType', []),
    //                 false,
    //                 $this->logger))
    //                 ->handle();
                    
    //         } catch (\Exception $e) {
    //             $this->logger->error(sprintf("%s LỖI: %s", $manga['slug'], $e->getMessage()));
    //             printf("%s LỖI: %s\n", $manga['slug'], $e->getMessage());
    //             $count_error++;
    //         }
    //     }
    //     $this->logger->notice(sprintf("Hoàn thành tải truyện (TỔNG: %d | ĐÃ XONG: %d | LỖI: %d)", $count_mangas, $count_mangas - $count_error, $count_error));
    //     printf("Hoàn thành tải truyện (TỔNG: %d | ĐÃ XONG: %d | LỖI: %d)\n", $count_mangas, $count_mangas - $count_error, $count_error);
        
    //     // Lấy thời điểm kết thúc thực hiện command
    //     $end_time = microtime(true);
    //     // Tính toán thời gian chạy command (tính bằng giây)
    //     $execution_time = round($end_time - $start_time, 2);
        
    //     Artisan::call('optimize:clear');

    //     // Ghi log thời gian chạy command
    //     $this->logger->notice(sprintf("Tổng thời gian: %s giây", $execution_time));
    //     printf("Tổng thời gian: %s giây\n", $execution_time);

    //     return 0;
    // }


    //optimized version
    public function handle()
{
    // Ghi nhận thời gian bắt đầu
    $start_time = microtime(true);

    // Lấy ngày hiện tại
    $current_date = date('Y-m-d H:i:s');

    // Nếu không được phép crawl, thoát ngay lập tức
    if (!$this->checkCrawlerScheduleEnable()) {
        return 0;
    }

    // Thiết lập các thông số ban đầu
    $page_from = Option::get('crawler_schedule_page_from', 1);
    $page_to = Option::get('crawler_schedule_page_to', 3);

    $this->logger->notice(sprintf("Crawler Page (FROM: %d | TO: %d)",  $page_from, $page_to));
        printf("[%s] Tải truyện theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $current_date, $page_from, $page_to);

    // Thay vì giữ toàn bộ dữ liệu trong một collection, hãy xử lý từng trang riêng lẻ
    for ($page = $page_from; $page <= $page_to; $page++) {
        // Kiểm tra xem có tiếp tục crawl hay không
        if (!$this->checkCrawlerScheduleEnable()) {
            $this->logger->notice("Crawler stopped");
            return 0;
        }

        // Lấy dữ liệu trang và xử lý
        $response = Http::timeout(30)->get(Option::get('domain') . "/danh-sach/truyen-moi", ['page' => $page]);
        $responseData = json_decode($response->getBody(), true);

        // Nếu không có dữ liệu hợp lệ, tiếp tục trang tiếp theo
        if (!$responseData['status'] || empty($responseData['data']['items'])) {
            continue;
        }

        // Xử lý các mục trong trang này
        foreach ($responseData['data']['items'] as $index => $manga) {
            // Kiểm tra nếu dừng lại
            if (!$this->checkCrawlerScheduleEnable()) {
                $this->logger->notice("Crawler stopped during processing");
                return 0;
            }

            try {
                // Tải và xử lý manga
                $manga_link = Option::get('domain') . "/truyen-tranh/" . $manga['slug'];
                $this->logger->notice(sprintf("Processing %s", $manga['name']));
                
                // Tạo Crawler cho từng manga
                $crawler = new Crawler(
                    $manga_link,
                    Option::get('crawler_schedule_fields', []),
                    Option::get('crawler_schedule_excludedCategories', []),
                    Option::get('crawler_schedule_excludedType', []),
                    false,
                    $this->logger
                );

                $crawler->handle();  // Xử lý dữ liệu manga

            } catch (\Exception $e) {
                $this->logger->error(sprintf("%s error: %s", $manga['slug'], $e->getMessage()));
            }
        }
    }

    Artisan::call('optimize:clear');

    // Ghi nhận thời gian kết thúc và tính thời gian chạy
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);

    // Ghi log và hiển thị thời gian chạy
    // $this->logger->notice(sprintf("Total execution time: %s seconds", $execution_time));
    // printf("Total execution time: %s seconds\n", $execution_time);
    $this->logger->notice(sprintf("[%s] Total execution time: %s seconds", $current_date, $execution_time));
    printf("[%s] Total execution time: %s seconds\n", $current_date, $execution_time);

    return 0;
}


    public function checkCrawlerScheduleEnable()
    {
        return Option::get('crawler_schedule_enable', false);
    }
}
