<?php

namespace App\Console\Commands\TruyenTranhNetCommands;

use App\Helpers\CurlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ophim\Crawler\OphimCrawler\TruyenTranhNetCrawler\TruyenTranhNetCrawler;
use Symfony\Component\DomCrawler\Crawler;

class TruyenTranhNetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truyentranhnet-crawler';

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

    $page_from = 1;
    $page_to = 156;

    printf("[%s] Tải truyện từ truyentranh.net.vn theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $current_date, $page_to, $page_from);
    
    for ($page = $page_to; $page >= $page_from; $page--) {
        $url = "https://truyentranh.net.vn/tim-truyen?status=2&sort=0&page={$page}";

        try {
        $items = $this->getMangaList($url);
        // print_r($items);

            foreach ($items as $item) {
            $manga_item_data = $this->getMangaItemData($item)['data']['item'];
            $manga_url = $manga_item_data['url'];
            
            $crawler = new TruyenTranhNetCrawler($manga_url);
            $crawler->handle();  // Xử lý dữ liệu manga
        }
    } catch (\Throwable $th) {
        //throw $th;
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
        $body = CurlHelper::fetchHtmlViaNoProxy($url);
        $crawler = new Crawler($body);
        $items = $crawler->filterXPath('.//div[@class="grid md:grid-cols-4 grid-cols-2 gap-3"]/div[@class="col-span-1"]/div[@class="relative"]/a');
        return $items;
    }

    public function getMangaItemData($item){
        $itemCrawler = new Crawler($item);
        $manga_url = $itemCrawler->filter('a')->attr('href') ?? '';
        $manga_name = $itemCrawler->filter('a')->attr('title') ?? '';

        $manga = [];
        $manga['name'] = $manga_name;
        $manga['url'] = "https://truyentranh.net.vn". $manga_url;

        return [
            "data" => [
                'item' => $manga,
            ]
        ];
    }

}
