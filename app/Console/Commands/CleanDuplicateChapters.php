<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Chapter;

class CleanDuplicateChapters extends Command
{
    protected $signature = 'chapters:clean-duplicates';
    protected $description = 'Xóa các chapter trùng lặp dựa trên chapter_number và manga_id';

    public function handle()
    {
        $this->info("Bắt đầu làm sạch dữ liệu bảng chapters...");

        // Tìm các chapter trùng lặp
        $duplicates = Chapter::select('manga_id', 'chapter_number', DB::raw('MAX(id) as max_id'))
            ->groupBy('manga_id', 'chapter_number')
            ->havingRaw('COUNT(*) > 1') 
            ->get();

        foreach ($duplicates as $duplicate) {
            Chapter::where('manga_id', $duplicate->manga_id)
                ->where('chapter_number', $duplicate->chapter_number)
                ->where('id', '!=', $duplicate->max_id)
                ->delete();

            $this->info("Xóa các bản chapter trùng lặp với manga_id: {$duplicate->manga_id}, chapter_number: {$duplicate->chapter_number}");
        }

        $this->info("Làm sạch dữ liệu hoàn tất.");
    }
}

