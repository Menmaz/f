<?php

namespace App\Console\Commands\Manga18clubCommands;

use App\Helpers\CurlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Ophim\Crawler\OphimCrawler\Manga18clubCrawler\Manga18clubCrawler;
use Symfony\Component\DomCrawler\Crawler;

class Manga18clubCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga18club-crawler';

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
    $start_time = microtime(true);
    $current_date = date('Y-m-d H:i:s');

    $page_from = 1;
    $page_to = 4;

    printf("[%s] Tải truyện từ manga18.club theo số trang (TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $current_date, $page_to, $page_from);
    
    for ($page = $page_to; $page >= $page_from; $page--) {
        $url = "https://manga18.club/list-manga/{$page}";

        try {
        $items = $this->getMangaList($url);
        // print_r($items);
        
            foreach ($items as $item) {
            $manga_item_data = $this->getMangaItemData($item)['data']['item'];
            $manga_url = $manga_item_data['url'];
            print_r($manga_url . "\n");
            
            // $crawler = new Manga18clubCrawler($manga_url);
            // $crawler->handle();  // Xử lý dữ liệu manga
        }
    } catch (\Throwable $th) {
        
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
        $body = CurlHelper::fetchHtmlViaProxy($url);
        $crawler = new Crawler($body);
        $items = $crawler->filterXPath('.//div[@class="col-md-3 col-sm-4 col-xs-6"]');
        return $items;
    }

    public function getMangaItemData($item){
        $itemCrawler = new Crawler($item);
        $manga_url = $itemCrawler->filterXPath('.//a')->attr('href') ?? '';
        $manga_name = $itemCrawler->filterXPath('.//a')->attr('title') ?? '';

        $manga = [
            'name' => $manga_name,
            'url' => $manga_url,
        ];

        return [
            "data" => [
                'item' => $manga,
            ]
        ];
    }

}
