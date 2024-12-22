<?php

namespace Ophim\Core\Models;

use App\Jobs\IncrementViewCount;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Traits\HasFactory;

// use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model 
{
  use HasFactory;
  use CrudTrait;
  use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;

  protected $table = 'chapters';

  protected $fillable = ["content", "content_sv2",  "chapter_number", "title", "manga_id", "user_id", "username", "deleted_at", "created_at", "updated_at", "status"];

  protected $casts = [
    "content" => "array",
    "content_sv2" => "array",
  ];

  public function user()
  {
    return $this->belongsTo(User::class, "user_id");
  }

  public function manga()
  {
    return $this->belongsTo(Manga::class, "manga_id");
  }

  public function comments()
  {
    return $this->morphMany(Comment::class, "commentable");
  }

  public function views()
  {
    return $this->hasMany(View::class, "key")->where("model", self::class);
  }

  //FUNCTIONS

  // protected function replaceImageDomain($imageUrl, $newDomain){
  //   $httpsPosition = strpos($imageUrl, 'https://');
  //   $httpPosition = strpos($imageUrl, 'http://');

  //   if ($httpsPosition !== false || $httpPosition !== false) {
  //       $slashPosition = strpos($imageUrl, '/', $httpsPosition !== false ? $httpsPosition + strlen('https://') : $httpPosition + strlen('http://'));

  //       if ($slashPosition !== false) {
  //           $oldDomain = substr($imageUrl, 0, $slashPosition);
  //           $remainingPath = substr($imageUrl, $slashPosition);  

  //           $imageUrl = $newDomain . $remainingPath;
  //       } 
  //   }
    
  //   return $imageUrl;
  // }

  public function getImageUrl($imageUrl){
  //   $originImageDomainServer1 = config('custom.contabo_domain');
  //   $imageDomainServer1 = Setting::where('key', 'contabo_image_domain')->value('value');
  //   $originImageDomainServer2 = config('custom.pixeldrain_domain');
  //   $imageDomainServer2 = Setting::where('key', 'pixeldrain_image_domain')->value('value');
  //   if (strpos($imageUrl, $originImageDomainServer2) !== false) {
  //     $imageUrl = str_replace($originImageDomainServer2, $imageDomainServer2, $imageUrl);
  // } else if (strpos($imageUrl, $originImageDomainServer1) !== false || strpos($imageUrl, "https://img.giatot.xyz") !== false) {
  //     $imageUrl = str_replace($originImageDomainServer1, $imageDomainServer1, $imageUrl);
  // }

  return $imageUrl;
  }

  public function totalViews()
  {
    return View::where("model", self::class)
      ->where("key", $this->id)
      ->sum("views");
    // return $this->views->sum("views");
  }

    public function addView()
    {
      // Dispatch the job with a 30-second delay
      IncrementViewCount::dispatch($this->id)->delay(now()->addSeconds(30));
    }

    public function whereHasCrawlerChapter($query)
    {
        return $query->whereHas('crawlerChapters', function ($query) {
            $query->whereIn('status', ['trailer', 'ongoing', 'completed']);
        });
    }

    public static function getTotalViews()
    {
        return View::where("model", self::class)->sum("views");
    }

    public function crawlerChapters()
    {
        return $this->hasMany(CrawlerChapter::class, 'id_chapter');
    }

    public function previewChapterImages(){
      return '<a class="btn btn-sm btn-link" target="_blank" data-toggle="tooltip" title="chapter"><i class="la la-book"></i> Xem trước</a>';
    }


    public function previewServer1($crud = false)
{  
    $html = '
    <button class="btn btn-sm btn-link open-modal'.$this->id.'" data-target="#chapterModal'.$this->id.'" data-toggle="modal"><i class="la la-book"></i> Xem trước (Server 1)</button>
    <div class="modal fade" id="chapterModal'.$this->id.'" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="box-shadow: rgba(0, 0, 0, 0.24) 0px 6px 16px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"> Xem trước : Chapter '.$this->chapter_number.'</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="nav nav-tabs modal-body" style="overflow-y: scroll;max-height: 600px;">
                <!-- Modal content goes here -->
            </div>
        </div>
    </div>
</div>
';

$script = "
<script>
        $('.open-modal$this->id').click(function() {
            var images = '';";
$imageUrls = $this->content;
if($imageUrls){
  foreach ($imageUrls as $imageUrl) {
    $script .= "images += '<img class=\"item\" src=\"" . $this->getImageUrl($imageUrl) . "\" style=\"max-width: 100%; height: auto;\">'; ";
}
} else {
    $script .= "images += '<h4>Chưa có trang nào !</h4>';";
} 
            
$script .= "var html = images;
            $('#chapterModal$this->id .modal-body').html(html);
            $('#chapterModal$this->id').modal({
              backdrop: false
            })
        });
</script>";
    return $html . $script;
}

public function previewServer2()
{   
    $html = '
    <button class="btn btn-sm btn-link open-modal-server2'.$this->id.'" data-target="#chapterModalServer2'.$this->id.'" data-toggle="modal"><i class="la la-book"></i> Xem trước (Server 2)</button>
    <div class="modal fade" id="chapterModalServer2'.$this->id.'" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="box-shadow: rgba(0, 0, 0, 0.24) 0px 6px 16px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"> Xem trước : Chapter '.$this->chapter_number.'</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="nav nav-tabs modal-body" style="overflow-y: scroll;max-height: 600px;">
                <!-- Modal content goes here -->
            </div>
        </div>
    </div>
</div>
';

$script = "
<script>
        $('.open-modal-server2$this->id').click(function() {
            var images = '';";
$imageUrls = $this->content_sv2;
if($imageUrls){
foreach ($imageUrls as $imageUrl) {
    $script .= "images += '<img class=\"item\" src=\"" . $this->getImageUrl($imageUrl) . "\" style=\"max-width: 100%; height: auto;\">'; ";
}
} else {
    $script .= "images += '<h4>Chưa có trang nào !</h4>';";
} 
            
$script .= "var html = images;
            $('#chapterModalServer2$this->id .modal-body').html(html);
            $('#chapterModalServer2$this->id').modal({
              backdrop: false
            })
        });
</script>";
    return $html . $script;
}


public function crawlChapterButton(){
  $manga_slug = $this->manga()->value('slug');
  return '<a class="btn btn-sm btn-link" target="_blank" href="'.backpack_url("chapters/crawl_chapter/{$manga_slug}/{$this->id}").'"><i class="la la-download"></i> Tải chapter</a>';
}

public function chapterActionButtons() {
  $manga_slug = $this->manga()->value('slug');

  // Tạo nút "Thêm chapter mới"
  $create_button = '<a class="btn btn-sm btn-link" href="' . backpack_url("chapters/{$manga_slug}/create") . '"><i class="la la-plus"></i> Thêm chapter mới</a>';

  // Tạo nút "Sửa chapter"
  $edit_button = '<a class="btn btn-sm btn-link" href="' . backpack_url("chapters/{$manga_slug}/{$this->id}/edit") . '"><i class="la la-edit"></i> Sửa</a>';

  return $create_button . ' ' . $edit_button;
}

public function getContentAttribute(){
        $content = $this->attributes['content'];
        $refferer = config('custom.nettruyen_domain');

        $contentArray = json_decode($content, true);
    
    //     foreach ($contentArray as &$page) {
    //     $page = str_replace(
    //         "truyen.taxoakumi.xyz/api/v1/get-bp-image?", 
    //         "hihitruyen.com/get-bp-image?referrer=$refferer&", 
    //         $page
    //     );
    // }
    
    $oldDomain = "truyen.taxoakumi.xyz/api/v1/get-bp-image?";
    $newDomain = "trieubui.top/get-bp-image?referrer=$refferer&image_url=";
    
    foreach ($contentArray as &$page) {
        if (strpos($page, $oldDomain) !== false && preg_match('/image_url=([a-zA-Z0-9=]+)/', $page, $matches)) {
            $base64ImageUrl = $matches[1]; 
            $decodedImageUrl = base64_decode($base64ImageUrl); 
            $page = str_replace($oldDomain . "image_url=$base64ImageUrl", $newDomain . urlencode($decodedImageUrl), $page);
        }
    }
    
        return $contentArray;
    }


}

