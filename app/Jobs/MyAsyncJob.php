<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ophim\Core\Models\Chapter;
use Ophim\Crawler\OphimCrawler\NettruyenCrawler;

class MyAsyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $manga;
    protected $chapter_api_url;
    protected $nettruyenCrawler;
    protected $link;

    public function __construct($manga, $chapter_api_url,  $link = null)
    {
        $this->manga = $manga;
        $this->chapter_api_url = $chapter_api_url;
        $this->link = $link;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $nettruyenCrawler = new NettruyenCrawler($this->link);
        
        // Use the NettruyenCrawler instance to crawl the chapter
        $nettruyenCrawler->crawl($this->chapter_api_url);
    }
}
