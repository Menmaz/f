<?php

namespace Ophim\Crawler\OphimCrawler;

use App\Helpers\ImageHelper;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Ophim\Core\Models\Taxable;
use Ophim\Crawler\OphimCrawler\Contracts\BaseCrawler;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\DB;

class Crawler extends BaseCrawler
{
    //otruyen version
    public function handle()
    {
        $startTime = microtime(true);

        $response = $this->client->request('GET', $this->link);
        $body = $response->getBody()->getContents();
        $payload = json_decode($body, true);
        
        $mangaResponseData = $payload['data']['item'];

        $this->checkIsInExcludedList($mangaResponseData);

        $this->logMessage(sprintf("- Đang tải truyện '%s'...", $mangaResponseData['name']));

        $manga = Manga::where('slug', $mangaResponseData['slug'])->first();

        $info = (new Collector($payload, $this->fields, $this->forceUpdate))->get();

        if ($manga) {
            $this->updateManga($manga, $mangaResponseData);
        } else {
           $manga = $this->createManga($info, $mangaResponseData);
        }

        $this->syncCategories($manga, $mangaResponseData);
        $this->syncChapters($manga, $mangaResponseData);

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->logMessage(sprintf("=> Đã tải xong truyện '%s' trong %s giây", $mangaResponseData['name'], $executionTime));
    }

    private function updateManga($manga, $mangaResponseData)
    {
        // $shouldSave = false;

        // if (!str_contains($manga->cover, "https://img.giatot.xyz/")) {
        //     // $manga->save();
        //     $shouldSave = true;
        // } else {
        //     $shouldSave = true;
        // }

        // // if (!str_contains($manga->description, "-10Truyen.com")) {
        // //     $newChatGPTContent = $this->changeContentWithChatGPT($mangaResponseData['content']);
        // //     $manga->description = $newChatGPTContent;
        // //     // $manga->save();
        // //     $shouldSave = true;
        // // }

        // if ($shouldSave) {
        //     $manga->save();
        // }
    }

    private function createManga($info, $mangaResponseData)
    {
        // $newChatGPTContent = $this->changeContentWithChatGPT($mangaResponseData['content']);
        // $info['content'] = $newChatGPTContent;
        return Manga::create($info);
    }

    private function logMessage($message)
    {
        if($this->logger != null){
            $this->logger->notice($message);
        }
        printf("%s\n", $message);
    }

    //otruyen version
    protected function checkIsInExcludedList($mangaResponseData)
    {
        //     $newType = $payload['movie']['type'];
    //     if (in_array($newType, $this->excludedType)) {
    //         throw new \Exception("Thuộc định dạng đã loại trừ");
    //     }

        $newCategories = collect($mangaResponseData['category'])->pluck('name')->toArray();
        if (array_intersect($newCategories, $this->excludedCategories)) {
            throw new \Exception("Thuộc thể loại đã loại trừ");
        }
    }

    //otruyen version
    protected function syncCategories($manga, array $mangaResponseData)
    {
        $categories = [];
        $status = $mangaResponseData['status'];
        foreach ($mangaResponseData['category'] as $category) {
            if (!trim($category['name'])) continue;
            $category = Taxonomy::where('name', trim($category['name']))->first();
            if ($category) {
                $categories[] = $category->id;
            }
        }
        $status = Taxonomy::where('slug', trim($status))->first();
            if ($status) {
                $categories[] = $status->id;
            }
        if ($manga) {
                $manga->taxanomies()->sync($categories);
        }
        foreach($categories as $category){
            Taxable::firstOrCreate([
                'taxonomy_id' => $category,
                'taxable_id' => $manga->id
            ]);
        }
    }

    //đổi nội dung mô tả của manga bằng chat gpt
    protected function changeContentWithChatGPT($content) {
  
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key, // Set authorization header
        ]);
    
        $response = curl_exec($ch);s
        if(curl_errno($ch)){
            echo 'Curl error: ' . curl_error($ch);
        }
    
        curl_close($ch);
        $result = json_decode($response, true);
    
        return $result['choices'][0]['message']['content'];
    } 


//xử lý chapter (bất đồng bộ)
protected function syncChapters($manga, $mangaResponseData)
{
    $this->logMessage(sprintf("- Bắt đầu tải chapter cho truyện '%s'...", $mangaResponseData['name']));
    $startTime = microtime(true);

    $client = $this->client;

    $concurrency = 10; // Số lượng yêu cầu đồng thời tối đa

    $requests = function () use ($client, $mangaResponseData) {
        foreach ($mangaResponseData['chapters'] as $server) {
            foreach ($server['server_data'] as $chapter) {
                $chapter_api_url = $chapter['chapter_api_data'];
                yield function () use ($client, $chapter_api_url) {
                    return $client->getAsync($chapter_api_url);
                };
            }
        }
    };

    $pool = new \GuzzleHttp\Pool($client, $requests(), [
        'concurrency' => $concurrency,
        'fulfilled' => function ($response, $index) use ($manga) {
            if ($response->getStatusCode() === 200) {
            $chapter_data = json_decode($response->getBody());

            if (isset($chapter_data->data->item)) {
            $chapter_detail_data = $chapter_data->data->item;
            $chapter_number = floatval($chapter_detail_data->chapter_name);
            $image_chapter_domain = $chapter_data->data->domain_cdn;

            // Lấy nội dung hình ảnh của chương
            $content = array_map(function ($chapter_image) use ($image_chapter_domain, $chapter_detail_data) {
                return $image_chapter_domain . '/' . $chapter_detail_data->chapter_path . '/' . $chapter_image->image_file;
            }, $chapter_detail_data->chapter_image ?? []);

            // Tạo hoặc cập nhật chương trong cơ sở dữ liệu
            $chapterInDB = Chapter::where('manga_id', $manga->id)->where('chapter_number', $chapter_number)->first();
            if ($chapterInDB) {
                if($chapterInDB->content === null){
                    $chapterInDB->update([
                        'content' => $content,
                        'content_sv2' => $content,
                        'status' => 'waiting_to_upload'
                    ]);
                }
            } else {
                $this->logMessage("Đang thêm chapter mới cho truyện: {$manga->slug}, chapter_number: {$chapter_number}");

                // Tìm chapter trước và sau để xác định thời gian tạo
                $previousChapter = Chapter::where('manga_id', $manga->id)
                ->where('chapter_number', '<', $chapter_number)
                ->orderBy('chapter_number', 'desc')
                ->first();

            $nextChapter = Chapter::where('manga_id', $manga->id)
                ->where('chapter_number', '>', $chapter_number)
                ->orderBy('chapter_number', 'asc')
                ->first();

            $createdAt = now(); // Thời gian tạo mặc định là hiện tại

            // Điều chỉnh thời gian tạo nếu có chapter trước hoặc sau
            if ($previousChapter && $nextChapter) {
                $createdAt = $nextChapter->created_at->copy()->subSecond();
            } elseif ($nextChapter) {
                $createdAt = $nextChapter->created_at->copy()->subSecond();
            } elseif ($previousChapter) {
                $createdAt = $previousChapter->created_at->copy()->addSecond();
            }

                Chapter::create([
                    'title' => $chapter_detail_data->chapter_title ?? '',
                    'chapter_number' => $chapter_number,
                    'manga_id' => $manga->id,
                    'content' => $content,
                    'content_sv2' => $content,
                    'status' => 'waiting_to_upload',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt
                ]);
            }
        } else {
            $this->logMessage("Dữ liệu chapter không hợp lệ nhận được cho truyện: {$manga->slug}");
        }
        } else {
            $this->logMessage("Không thể tải dữ liệu chapter cho truyện: {$manga->slug}, Mã trạng thái: " . $response->getStatusCode());
        }
        }
    ]);

    $promise = $pool->promise();
    $promise->wait(); // Đợi tất cả các yêu cầu hoàn thành
     
    $promise = null;
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    $this->logMessage(sprintf("- Đã tải xong chapter cho truyện '%s' trong %s giây.", $mangaResponseData['name'], $executionTime));
}


}
