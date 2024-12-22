<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Ophim\Core\Models\Manga;
use Ophim\Crawler\OphimCrawler\Option;

class UpdateMangaDatetimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-manga-datetime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update datetime of manga';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $client;
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
    $this->info('Starting update datetime process...');
    
    // Ghi nhận thời gian bắt đầu
    $start_time = microtime(true);
    $page_from = 1;
    $page_to = 917;

    printf("(TỪ TRANG: %d | ĐẾN TRANG: %d)\n", $page_from, $page_to);


    // Thay vì giữ toàn bộ dữ liệu trong một collection, hãy xử lý từng trang riêng lẻ
    for ($page = $page_from; $page <= $page_to; $page++) {

        // Lấy dữ liệu trang và xử lý
        $response = Http::timeout(30)->get(Option::get('domain') . "/danh-sach/truyen-moi", ['page' => $page]);
        $responseData = json_decode($response->getBody(), true);

        // Nếu không có dữ liệu hợp lệ, tiếp tục trang tiếp theo
        if (!$responseData['status'] || empty($responseData['data']['items'])) {
            continue;
        }

        // Xử lý các mục trong trang này
        foreach ($responseData['data']['items'] as $index => $manga) {

            try {
                $m = Manga::where('slug', $manga['slug'])->first();
                if ($m) {
                    $before_datetime = $m->created_at;
                    $updatedAt = Carbon::parse($manga['updatedAt']);
                    $m->update(['created_at' => $updatedAt]);
                    printf("%s. %s success: created_at %s => %s\n", $index, $manga['slug'], $before_datetime, $m->created_at);
                }
            } catch (\Exception $e) {
                printf("%s error: %s", $manga['slug'], $e->getMessage());
            }
        }
    }

    // Ghi nhận thời gian kết thúc và tính thời gian chạy
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);


    printf("Total execution time: %s seconds\n", $execution_time);

    return 0;

    $this->info('Update process completed.');
}


protected function updateManga($manga)
{
    
}

}
