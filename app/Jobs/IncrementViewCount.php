<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\View;

class IncrementViewCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chapterId;

    public function __construct($chapterId)
    {
        $this->chapterId = $chapterId;
    }

    public function handle()
{
    $query = View::where([
        'model' => Chapter::class,
        'key' => $this->chapterId,
    ])
    ->whereDate('created_at', '=', Carbon::now()->format('Y-m-d'));

    // Nếu người dùng đã đăng nhập, thêm điều kiện 'user_id'
    if (auth()->check()) {
        $query->where('user_id', auth()->user()->id);
    }

    $existingView = $query->first();

    if ($existingView) {
        $existingView->increment('views');
    } else {
        $view = new View([
            'model' => Chapter::class,
            'key' => $this->chapterId,
            'views' => 1,
            'created_at' => Carbon::today(),
        ]);

        // Nếu người dùng đã đăng nhập, thêm 'user_id' vào
        if (auth()->check()) {
            $view->user_id = auth()->user()->id;
        }

        $view->save();
    }
}
}
