<?php

namespace App\Console\Commands\TruyenVNCommands;

use App\Helpers\CurlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ophim\Crawler\OphimCrawler\TruyenVNCrawler\TruyenVNCrawler;
use Symfony\Component\DomCrawler\Crawler;
class TruyenVNCrawlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truyenvn-crawler';

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
    $page_to = 700;

    printf("[%s] Tải truyện từ truyenvn.me theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $current_date, $page_from, $page_to);
    
    for ($page = $page_from; $page <= $page_to; $page++) {
        $url = "https://truyenvn.fit/truyen-tranh/page/{$page}/";

        try {
        $items = $this->getMangaList($url);

            foreach ($items as $item) {
            $manga_item_data = $this->getMangaItemData($item)['data']['item'];
            $manga_url = $manga_item_data['url'];
            
            $crawler = new TruyenVNCrawler($manga_url);
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
        $body = CurlHelper::fetchHtmlViaProxy($url, "https://truyenvn.fit/wp-content/uploads/2024/02/cropped-Truyenvn-favicon-32x32.png");
        $crawler = new Crawler($body);
        $items = $crawler->filterXPath('.//div[starts-with(@id, "manga-item-")]');
        return $items;
    }

    public function getMangaItemData($item){
        $itemCrawler = new Crawler($item);
        $manga_url = $itemCrawler->filter('a')->attr('href') ?? '';
        $manga_name = $itemCrawler->filter('a')->attr('title') ?? '';

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
