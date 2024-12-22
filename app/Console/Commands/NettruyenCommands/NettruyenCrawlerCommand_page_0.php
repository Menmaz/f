<?php

namespace App\Console\Commands\NettruyenCommands;

use App\Helpers\CurlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ophim\Crawler\OphimCrawler\NettruyenRecentChaptersCrawler;
use Symfony\Component\DomCrawler\Crawler;

class NettruyenCrawlerCommand_page_0 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nettruyen-crawler_page_0';

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


    printf("[%s] Tải truyện từ trang đầu tiên của nettruyen.cc\n", $current_date);

        $url = "https://nettruyencc.com/tim-truyen";

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
            
            //   // Tạo Crawler cho từng manga
            //     $crawler = new NettruyenRecentChaptersCrawler(
            //         $manga_url
            //     );

            //     $crawler->handle();
            print_r($manga_url);
        }

    Artisan::call('optimize:clear');
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);

    printf("[%s] Total execution time: %s seconds\n", $current_date, $execution_time);

    return 0;
    }


    protected function getMangaList($url)
    {
        $response = CurlHelper::fetchHtmlViaNoProxy($url);
        return $response;
    }

}
