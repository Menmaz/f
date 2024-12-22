<?php

namespace App\Console\Commands\NettruyenCommands;

use App\Helpers\CurlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ophim\Crawler\OphimCrawler\NettruyenCrawler;
use Symfony\Component\DomCrawler\Crawler;

class NettruyenCrawlerCommand_3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nettruyen-crawler_3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $context;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {   
        // Ghi nhận thời gian bắt đầu
    $start_time = microtime(true);

    // Lấy ngày hiện tại
    $current_date = date('Y-m-d H:i:s');

    $page_from = 0;
    $page_to = 50;

    printf("[%s] Tải truyện từ nettruyen.cc theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $current_date, $page_from, $page_to);

    for ($page = $page_from; $page <= $page_to; $page++) {
        $url = "https://nettruyencc.com/tim-truyen?page={$page}";

        $body = $this->getMangaList($url);
        $crawler = new Crawler($body);
        $items = $crawler->filterXPath('//div[@class="items"]/div[@class="row"]/div[@class="item"]');

        foreach ($items as $item) {
            // Tạo một đối tượng Crawler từ item hiện tại
            $itemCrawler = new Crawler($item);

            // Lấy thẻ div có class là 'image'
            $imageDiv = $itemCrawler->filter('.image');

            // Lấy href của thẻ a trong div image
            $manga_url = $imageDiv->filter('a')->attr('href');
            
               // Tạo Crawler cho từng manga
                $crawler = new NettruyenCrawler(
                    $manga_url,
                    [],
                    [],
                    [],
                    false,
                    false,
                );

                $crawler->handle();
        }
    }

    Artisan::call('optimize:clear');
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);

    printf("[%s] Total execution time: %s seconds\n", $current_date, $execution_time);

    return 0;
    }


    protected function getMangaList($url)
    {
        $response = CurlHelper::fetchHtmlViaProxy($url);
        return $response;
    }

}
