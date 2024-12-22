<?php

namespace App\Console\Commands;

use App\Jobs\MyAsyncJob;
use Illuminate\Console\Command;
use Ophim\Core\Controllers\Admin\SiteMapController;

class AutoSiteMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-sitemap';
    protected $description = 'Chạy sitemap tự động';

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
        $siteMapController = new SiteMapController();
        $siteMapController->store(request());
    }
}
