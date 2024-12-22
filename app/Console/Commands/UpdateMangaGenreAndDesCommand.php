<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Taxonomy;
use Ophim\Core\Models\Taxable;

class UpdateMangaGenreAndDesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-manga-genre-des';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update genre and des of manga';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
{    
    printf("Start");
    $mangas = Manga::select('id', 'title', 'slug')->get();
    foreach($mangas as $manga){
        $threeRandomTaxonomyIds = Taxonomy::where('type', 'genre')->pluck('id')->random(3);
        printf("Đang cập nhật truyện %s...\n", $manga->title);
        $this->updateGenre($manga, $threeRandomTaxonomyIds);
        $this->updateDes($manga);
        printf("Cập nhật truyện %s thành công\n", $manga->title);
    }

    return 0;
    printf("Completed");
}


protected function updateGenre($manga, $threeRandomTaxonomyIds)
{
    Taxable::firstOrCreate([
        'taxonomy_id' => 4,
        'taxable_id' => $manga->id
    ]);
    foreach($threeRandomTaxonomyIds as $taxonomyId){
        $exists = Taxable::where('taxonomy_id', $taxonomyId)
                          ->where('taxable_id', $manga->id)
                          ->exists();
        if (!$exists) {
            Taxable::firstOrCreate([
                'taxonomy_id' => $taxonomyId,
                'taxable_id' => $manga->id 
            ]);
        }
    }

    // Đảm bảo mỗi manga chỉ có tối đa 3 Taxable
    $taxables = Taxable::where('taxable_id', $manga->id)->get();
    if ($taxables->count() > 3) {
        // Xoá các Taxable thừa
        $taxables->skip(3)->each(function ($taxable) {
            $taxable->delete();
        });
    }
}

protected function updateDes($manga)
{
    if($manga->description != null){
        $manga->update(['description' => 'Đọc truyện ' . $manga->title]);
    }
}

}
