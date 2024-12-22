<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Crawler\OphimCrawler\ChapterCrawler;

class ChapterCrawlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otruyen:chapter-crawler:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawler chapter schedule command';

    protected $logger;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->logger = Log::channel('chapter-crawler');
        parent::__construct();
    }

    public function handle()
    {
        $start_time = microtime(true);
        $current_date = date('Y-m-d H:i:s');

        printf("[%s] Bắt đầu upload các chapter, vui lòng đợi...\n", $current_date);

        // $chapters = Chapter::whereNull('status')
        // ->orWhere('status', 'waiting_to_upload')
        // ->with('manga:id,slug,title')
        // ->get();

        // $count_error = 0;
        // $count_chapters = count($chapters);
        // foreach($chapters as $key => $chapter){
        //     try {
        //         $mangaOfChapter = $chapter->manga;
        //         printf("%d. Đang upload chapter %s cho truyện '%s'%s", $key + 1, $chapter->chapter_number, $mangaOfChapter->title, PHP_EOL);
        //         // $this->logger->notice(sprintf("%d. Đang upload chapter %s cho truyện '%s'", $key, $chapter->chapter_number, $mangaOfChapter->title));
        //         // printf("%d. Đang upload chapter %s cho truyện '%s'", $key, $chapter->chapter_number, $mangaOfChapter->title);
        //         $chapterCrawler = (new ChapterCrawler($mangaOfChapter, $chapter, $this->logger))->handle();
        //     } catch (\Exception $e) {
        //         $this->logger->error(sprintf("%s ERROR: %s", $mangaOfChapter->title, $e->getMessage()));
        //         $count_error++;
        //     }
        // }

        $count_error = 0;
        $count_chapters = 0;

        Chapter::orderBy('created_at', 'desc')
        ->whereNull('status')
        ->orWhere('status', 'waiting_to_upload')
        ->with('manga:id,slug,title')
        ->chunk(10, function($chapters) use (&$count_error, &$count_chapters) {
        foreach ($chapters as $key => $chapter) {
            try {
                $mangaOfChapter = $chapter->manga;
                printf("- Đang upload chapter %s cho truyện '%s'%s", $chapter->chapter_number, $mangaOfChapter->title, PHP_EOL);
                (new ChapterCrawler($mangaOfChapter, $chapter, $this->logger))->handle();
            } catch (\Exception $e) {
                $this->logger->error(sprintf("%s ERROR: %s", $mangaOfChapter->title, $e->getMessage()));
                $count_error++;
            }
            $count_chapters++;
        }
        });

        // $this->logger->notice(sprintf("Đã upload xong các chapters lên storage (TOTAL: %d | DONE: %d | ERROR: %d)", $count_chapters, $count_chapters - $count_error, $count_error));
        printf("Đã upload xong các chapters lên storage (TOTAL: %d | DONE: %d | ERROR: %d)", $count_chapters, $count_chapters - $count_error, $count_error);

        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 2);
        // $this->logger->notice(sprintf("Command execution time: %s seconds", $execution_time));
        printf("[%s] Command execution time: %s seconds", $current_date, $execution_time);

        return 0;
    }
}
