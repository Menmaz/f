<?php

namespace App\Console\Commands\TruyenVNCommands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Crawler\OphimCrawler\TruyenVNCrawler\TruyenVNCrawler;

class CheckMissingChaptersCommand extends Command
{
    protected $signature = 'truyenvn:check-missing-chapters';

    protected $description = 'Check missing chapters for mangas in TruyenVN';

    public function handle()
    {
        try {
            //code...
        // Lấy ngày hiện tại
        $current_date = date('Y-m-d H:i:s');
        printf("[%s] Đang thêm các chapter bị thiếu thông qua truyenvn.me\n", $current_date);
        $truyenVNCrawler = new TruyenVNCrawler("");

        $results = DB::select("
            SELECT m.slug AS manga_slug, missing_chapters.chapter_number
            FROM mangas m
            CROSS JOIN (
                SELECT manga_id, chapter_number
                FROM (
                    SELECT manga_id, chapter_number + 1 AS chapter_number
                    FROM chapters
                    UNION ALL
                    SELECT manga_id, 1 AS chapter_number
                    FROM chapters
                    WHERE chapter_number = (
                        SELECT MAX(chapter_number)
                        FROM chapters
                    )
                ) AS possible_chapters
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM chapters
                    WHERE chapters.manga_id = possible_chapters.manga_id
                    AND chapters.chapter_number = possible_chapters.chapter_number
                )
            ) AS missing_chapters
            WHERE m.id = missing_chapters.manga_id
            ORDER BY m.id, missing_chapters.chapter_number
        ");

        Log::info('Query executed successfully.', ['results' => $results]);


        if (count($results) > 0) {
            foreach ($results as $result) {
                $manga = Manga::where('slug', $result->manga_slug)->first();
                if($manga){
                    $chapterDataResponse = $truyenVNCrawler->getChapterData("https://truyenvn.me/truyen-tranh/{$result->manga_slug}/chapter-{$result->chapter_number}");
                $this->syncChapters($manga, $chapterDataResponse, $result->chapter_number);
                }else {
                    printf("Không tìm thấy truyện %s trong truyenvn.me\n", $manga->title);
                }
            }

         
        } else {
            $this->info('No missing chapters found.');
        }

        printf("Đã thêm các chapter bị thiếu\n");
    } catch (\Exception $th) {
        printf("%s", $th->getMessage());
    }
    }

    public function syncChapters($manga, $chapterDataResponse, $chapterNumber)
    {           
        printf("\n--Đã phát hiện chapter bị thiếu Chapter '%s' của truyện %s !", $chapterNumber, $manga->title);  
                // $startTime = microtime(true);

                // $chapterData = $chapterDataResponse['data']['item'];
                // $pageUrls = $chapterData['chapter_images'];
                    
                //     $content = array_map(function ($pageUrl) {
                //             // return 'https://truyen.taxoakumi.xyz/api/v1/get-bp-truyenvn-image?image_url=' . $pageUrl['image_file'];
                //         return 'http://localhost:8081/10truyenAPIs/public/api/v1/get-bp-truyenvn-image?image_url=' . $pageUrl['image_file'];
                //     }, $pageUrls ?? []);

                //     // Tìm chapter trước và sau để xác định thời gian tạo
                //     $previousChapter = Chapter::where('manga_id', $manga->id)
                //     ->where('chapter_number', '<', $chapterNumber)
                //     ->orderBy('chapter_number', 'desc')
                //     ->first();

                //     $nextChapter = Chapter::where('manga_id', $manga->id)
                //         ->where('chapter_number', '>', $chapterNumber)
                //         ->orderBy('chapter_number', 'asc')
                //         ->first();

                //     $createdAt = now();

                //     if ($previousChapter && $nextChapter) {
                //         $createdAt = $nextChapter->created_at->copy()->subSecond();
                //     } elseif ($nextChapter) {
                //         $createdAt = $nextChapter->created_at->copy()->subSecond();
                //     } elseif ($previousChapter) {
                //         $createdAt = $previousChapter->created_at->copy()->addSecond();
                //     }


                //     // print_r([
                //     //     'chapter_number' => $chapterNumber,
                //     //     // 'content' => $content,
                //     //     'created_at' => $createdAt
                //     // ]);

                //     Chapter::create([
                //                 'title' => '',
                //                 'chapter_number' => $chapterNumber,
                //                 'manga_id' => $manga->id,
                //                 'content' => $content,
                //                 'content_sv2' => $content,
                //                 'status' => 'waiting_to_upload',
                //                 'created_at' => $createdAt,
                //                 'updated_at' => $createdAt
                //     ]);

                //     //cập nhật cột updated_at cho manga
                //     $manga->update(['updated_at' => $createdAt]);

                //     unset($chapterData, $pageUrls, $content);
                    
                //     $endTime = microtime(true);
                //     $elapsedTime = $endTime - $startTime;
                //     $current_date = date('Y-m-d H:i:s');
                //     printf("\n-- [%s] Đã chapter bị thiếu Chapter '%s' cho truyện %s trong %.4f giây !", $current_date, $chapterNumber, $manga->title, $elapsedTime);  
    }

}
