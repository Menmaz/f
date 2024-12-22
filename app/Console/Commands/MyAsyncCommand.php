<?php

namespace App\Console\Commands;

use App\Jobs\MyAsyncJob;
use Illuminate\Console\Command;

class MyAsyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my:async-command';
    protected $description = 'Run an asynchronous task';

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
        for ($i = 0; $i < 5; $i++) {
            MyAsyncJob::dispatch();
        }
    }
}
