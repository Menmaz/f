<?php

namespace App\Console\Commands;

use App\Helpers\CurlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ophim\Crawler\OphimCrawler\TruyenqqCrawler;
use Symfony\Component\DomCrawler\Crawler;

class TruyenqqvietCrawlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truyenqqviet-crawler';

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
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: PHP"
            ]
        ];
        $this->context = stream_context_create($opts);
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

    // Thiết lập các thông số ban đầu
    // $page_from = Option::get('crawler_schedule_page_from', 1);
    // $page_to = Option::get('crawler_schedule_page_to', 3);
    $page_from = 1;
    $page_to = 2;

    printf("[%s] Tải truyện từ truyenqqviet.com theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $current_date, $page_from, $page_to);

    // $url = "https://nettruyencc.com/tim-truyen";
    // $body = $this->getMangaList($url);
        // print_r($body);
    
    for ($page = $page_from; $page <= $page_to; $page++) {
        $url = "https://truyenqqviet.com/truyen-moi-cap-nhat/trang-{$page}.html";
        $items = $this->getMangaList($url);

        foreach ($items as $item) {
            $manga_item = $this->getMangaItemData($item);
            $manga_data = $manga_item['data']['item'];
            $manga_url = $manga_data['url'];
            
                $crawler = new TruyenqqCrawler(
                    $manga_url,
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


    public function getMangaList($url)
    {
        $body = CurlHelper::fetchHtmlViaProxy($url, "https://st.truyenqqviet.com/template/frontend/images/favicon.ico");
        $crawler = new Crawler($body);
        $items = $crawler->filterXPath('.//ul[@class="list_grid grid"]/li');
        return $items;
    }

    public function getMangaItemData($item){
        $itemCrawler = new Crawler($item);
        $href = $itemCrawler->filter('a')->attr('href');
        $manga_url = "http://truyenqqviet.com" . $href;
        $manga_name = $itemCrawler->filterXPath('.//img')->attr('alt') ?? '';

        $manga = [];
        $manga['name'] = $manga_name;
        $manga['url'] = $manga_url;

        return [
            "data" => [
                'item' => $manga,
            ]
        ];
    }

}
