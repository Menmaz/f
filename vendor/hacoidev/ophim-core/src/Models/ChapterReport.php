<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ophim\Core\Traits\HasFactory;

class ChapterReport extends Model implements Cacheable
{
    use CrudTrait;
    use HasFactory;
    use HasCache;

    protected $table = 'chapter_reports';

    protected $fillable = ["chapter_id", "report_message",  "created_at", "updated_at"];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, "chapter_id");
    }

    public function getManga(){
        return $this->chapter->manga()->select('slug')->first();
    }

    public function openEpisode($crud = false)
    {
        return '<a class="btn btn-sm btn-link" target="_blank" href="'.url("admin/chapters/". $this->getManga()->slug).'" data-toggle="tooltip" title="Chỉ là một nút tùy chỉnh."><i class="fa fa-search"></i> Xem danh sách tập của truyện</a>';
    }

    public function openChapterInWeb($crud = false)
    {
        return '<a class="btn btn-sm btn-link" target="_blank" href="'.config('custom.website_url'). "/truyen/". $this->getManga()->slug .'" data-toggle="tooltip" title="Chỉ là một nút tùy chỉnh."><i class="fa fa-search"></i> Xem chi tiết</a>';
    }

}
