<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Traits\HasFactory;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class Badge extends Model implements Cacheable
{
    use CrudTrait;
    use HasFactory;
    use HasCache;

    protected $table = 'badges';
    protected $primaryKey = 'id';

    protected $fillable = ["image", "name", "description",  "comment_threshold", "css_color", "created_at", "updated_at"];

    // public function getImageUrl(){
    //     $imageDomainServer1 = Setting::where('key', 'image_domain_server_1')->value('value');
    //     $originImageDomainServer1 = config('custom.contabo_domain');
    //     $imageUrl = $this->image_url;
    //     // Nếu đường dẫn hình ảnh chứa domain gốc, thay thế bằng domain mới
    //     $newUrl = str_replace($originImageDomainServer1, $imageDomainServer1, $imageUrl);

    //     return $newUrl;
    // }

    //FUNCTIONS

}
