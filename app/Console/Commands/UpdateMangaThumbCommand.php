<?php

namespace App\Console\Commands;

use App\Helpers\ImageHelper;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Ophim\Core\Models\Manga;

class UpdateMangaThumbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-manga-thumb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update thumb of manga';

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

//     public function handle()
// {
//         $batchSize = 100;
//         Manga::orderByDesc('created_at')->select(['slug'])
//         ->where('cover', 'NOT LIKE', '%https://imgthumb.giatot.xyz/image_thumbs%')
//         ->chunk($batchSize, function ($mangas) {
//             foreach ($mangas as $manga) {
//             try {
//                     $mangaSlug = $manga->slug;
//                     $imageData = ImageHelper::optimizeImageUrl("https://img.otruyenapi.com/uploads/comics/{$mangaSlug}-thumb.jpg");

//                     if ($imageData instanceof \Illuminate\Http\JsonResponse) {
//                         continue;
//                     }

//                     if ($imageData instanceof \Illuminate\Http\Response) {
//                         $imageData = $imageData->getContent();
//                     }      

//                     $response = Http::asMultipart() 
//                     ->attach('image_data', $imageData, 'image.webp')
//                       ->post('https://imgthumb.giatot.xyz', [
//                           'manga_slug' => $mangaSlug, 
//                       ]);

//                     if ($response->successful()) {
//                         $uploadedImageUrl = 'https://imgthumb.giatot.xyz/image_thumbs/' . $mangaSlug . '_thumb.webp';
//                         printf("%s:", $uploadedImageUrl);
//                         DB::table('mangas')->where('slug', $manga->slug)->update(['cover' => $uploadedImageUrl]);
//                     } else {
//                         return 'Error in optimizing image';
//                     }
//             } catch (\Exception $e) {
//                 printf("%s error: %s", $manga['slug'], $e->getMessage());
//             }
//         }

//         gc_collect_cycles();
//     });
//     return 0;

//     $this->info('Update process completed.');
// }

public function handle()
{
    $batchSize = 100;
    $success_images = 0;
    $error_images = 0;
    Manga::select(['slug', 'cover'])
        ->chunk($batchSize, function ($mangas) use($success_images, $error_images) {
            foreach ($mangas as $index => $manga) {
            try {
                    $mangaSlug = $manga->slug;
                    $image_url = $manga->cover;
                    if ($this->isImageUrlValid($image_url)) {
                        $success_images ++;
                    } else {
                        $error_images++;
                        printf("%s) %s : %s (ERROR) \n", $index, $mangaSlug, $manga->cover);
                    }

            } catch (\Exception $e) {
                printf("%s error: %s", $manga['slug'], $e->getMessage());
            }
        }

    });

    printf('success: %d, error: %d', $success_images, $error_images);

    gc_collect_cycles();
    return 0;

    $this->info('Update process completed.');
}

private function isImageUrlValid($image_url)
    {
        $headers = @get_headers($image_url);
        if ($headers && strpos($headers[0], '200')) {
            return true;
        }
        return false;
    }


protected function updateManga($manga)
{
    
}

}
