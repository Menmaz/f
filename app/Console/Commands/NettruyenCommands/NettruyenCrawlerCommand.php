<?php

namespace App\Console\Commands\NettruyenCommands;

use App\Helpers\CurlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ophim\Crawler\OphimCrawler\NettruyenCrawler;
use Symfony\Component\DomCrawler\Crawler;

class NettruyenCrawlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nettruyen-crawler';

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
        ini_set('memory_limit', '1024M');  
        // Ghi nhận thời gian bắt đầu
    $start_time = microtime(true);

    // Lấy ngày hiện tại
    $current_date = date('Y-m-d H:i:s');

    $page_from = 0;
    $page_to = 300;

    printf("[%s] Tải truyện từ nettruyenhe.com theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $current_date, $page_from, $page_to);

    
    for ($page = $page_to; $page >= $page_from; $page--) {
        $url = "https://nettruyenhe.com/tim-truyen?page={$page}";
        $items = $this->getMangaList($url);

        foreach ($items as $item) {
            // $manga_item = $this->getMangaItemData($item);
            // $manga_data = $manga_item['data']['item'];
            // $manga_url = $manga_data['url'];
            $manga_url = $item;
            print_r($manga_url . "\n");
            // $crawler = new NettruyenCrawler(
            //         $manga_url
            // );

            // $crawler->handle();  // Xử lý dữ liệu manga
        }
    }

    Artisan::call('optimize:clear');
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);

    printf("[%s] Total execution time: %s seconds\n", $current_date, $execution_time);

    return 0;
    }


    // public function getMangaList($url)
    // {
    //     $body = CurlHelper::fetchHtmlViaProxy($url);
    //     $crawler = new Crawler($body);
    //     $items = $crawler->filterXPath('//div[@class="items"]/div[@class="row"]/div[@class="item"]');
    //     return $items;
    // }
    
    public function getMangaList($url)
    {
        // Lấy HTML từ URL
        $body = CurlHelper::fetchHtmlViaProxy($url);
        $crawler = new Crawler($body);
        
        // Lọc thẻ <script> có chứa JSON-LD
        $scriptNode = $crawler->filterXPath('.//script[@type="application/ld+json"]');
        if($scriptNode->count() > 0){
            $scriptTag = $scriptNode->text();
            // Giải mã JSON từ thẻ <script>
            $jsonData = json_decode($scriptTag, true);
        
            // Lấy danh sách URL từ itemListElement
            $items = $jsonData['itemListElement'] ?? [];
        
            // Tạo danh sách các URL
            $urls = [];
            foreach ($items as $item) {
                if (isset($item['url'])) {
                    $urls[] = $item['url'];
                }
            }
            
            return $urls;
        } else {
            return [];
        }
    }


    public function getMangaItemData($item){
        $itemCrawler = new Crawler($item);
        $imageDiv = $itemCrawler->filter('.image');
        $manga_url = $imageDiv->filter('a')->attr('href');
        $figcaption = $itemCrawler->filter('figcaption');
        $manga_name = $figcaption->filter('a')->text() ?? '';

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
