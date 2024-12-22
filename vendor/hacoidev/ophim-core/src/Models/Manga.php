<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ophim\Core\Traits\HasFactory;
use Illuminate\Support\Facades\URL as LARURL;

class Manga extends Model
{
    use CrudTrait;
    use HasFactory;
    use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;


    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'mangas';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    protected $fillable = [
        "title",
        "slug",
        "description",
        "author",
        "artist",
        "official_links",
        "track_links",
        "alternative_titles",
        "cover",
        "views",
        "rate",
        "likes",
        "user_id",
        "status",
        "releaseDate",
        "is_recommended",
        "is_shown_in_weekly",
        "showntimes_in_weekly",
        "showntimes_in_weekday",
        "showntimes_in_day",
        "created_at",
        "updated_at"
      ];

      protected static function boot()
    {
        parent::boot();

        // Thêm global scope để chỉ lấy Manga có ít nhất một Chapter
        static::addGlobalScope('hasChapters', function (Builder $builder) {
            $builder->has('chapters'); // Chỉ lấy Manga có ít nhất một Chapter
        });
    }

    public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, "user_id");
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class, "user_id");
  }

    public function taxables(): HasMany
{
    return $this->hasMany(Taxable::class, 'taxable_id');
}


    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, "manga_id");
    }

    public function latestChapter(): HasOne
    {
        return $this->hasOne(Chapter::class, "manga_id")->select(['chapter_number', 'created_at'])->latestOfMany('chapter_number');
    }

    public function latestChapters(): HasMany
    {
        return $this->HasMany(Chapter::class, "manga_id")->select(['chapter_number', 'created_at'])->latest('chapter_number');
    }

    public function taxanomies()
    {
        return $this->belongsToMany(Taxonomy::class, 'taxables', 'taxable_id', 'taxonomy_id');
    }

    // Lấy danh sách các thể loại
    public function genres()
  {
      return $this->taxanomies()
                  ->where(function($query) {
                      $query->where('type', 'genre')
                            ->orWhere('type', 'type');
                  })->select('name', 'slug');
  }
  
      public function types()
    {
      return $this->taxanomies()->where("type", "type")->select('name', 'slug');
    }


    public function statuses()
    {
      return $this->taxanomies()->where("type", "status");
    }

    public function lastestChapter()
    {
        return $this->chapters()->orderByDesc('chapter_number');
    }

    // public function latestChapter() {
    //   return $this->hasOne(Chapter::class, 'manga_id')
    //   ->latest('id'); // Tìm chương mới nhất
    // }
  
    //luot xem
    // public function views(): HasMany
    // {
    //   return $this->hasMany(View::class, "key")->where("model", Manga::class);
    // }

    public function views(): HasMany
    {
        return $this->hasMany(View::class, 'key')
            ->selectRaw('SUM(views) as total_views')
            ->where("model", Manga::class)
            ->groupBy('key');
    }

    public function getTotalViewsAttribute()
    {
        return number_format($this->views->sum('views'), 0, '', '.');
    }

    //binh luan
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, "commentable_id")->doesntHave('parentComment');
    }

    //đánh giá sao
    public function starRatings(): HasMany
    {
        return $this->hasMany(StarRating::class, "manga_id");
    }

    //đánh giá icon
    public function iconRatings(): HasMany
    {
        return $this->hasMany(IconRating::class, "manga_id");
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, "bookmarkable_id");
    }

    //các truyện liên quan
    public function relativeMangas()
    {
      $columnsToSelect = ['title', 'slug', 'cover'];
        $genreSlugs = $this->genres()->pluck('slug')->toArray();
        $relatedMangas = Manga::
            whereHas('genres', function ($query) use ($genreSlugs) {
                $query->whereIn('slug', $genreSlugs);
            })
            ->where('id', '!=', $this->id) 
            ->inRandomOrder() 
            ->limit(10)
            ->get($columnsToSelect);

        return $relatedMangas;
    }


    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

  public function addView(){
    $query = View::where([
      'model' => Manga::class,
      'key' => $this->id,
  ])
  ->whereDate('created_at', '=', Carbon::now()->format('Y-m-d'));

  if (auth()->check()) {
      $query->where('user_id', auth()->user()->id);
  }

  $existingView = $query->first();

  if ($existingView) {
    // Kiểm tra thời gian giữa hai lần xem
    $lastViewed = $existingView->updated_at ?? $existingView->created_at;
    $diffInMinutes = now()->diffInMinutes($lastViewed);

    // Nếu đã đủ 5 phút, tăng lượt view
    if ($diffInMinutes >= 5) {
            $existingView->increment('views');
            $existingView->touch(); // Cập nhật thời gian cập nhật view gần nhất
    }
  } else {
      $view = new View([
          'model' => Manga::class,
          'key' => $this->id,
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

    public function getUrl()
    {
        return LARURL::to('/') . "/truyen/$this->slug";
    }

    public function openView()
    {
        return '<a class="btn btn-sm btn-link" target="_blank" href="'.config('custom.frontend_url') .'/truyen/'.$this->slug.'" data-toggle="tooltip" title="Xem liên kết"><i class="la la-link"></i> Xem</a>';
    }

    public function showChapters()
    {
        return '<a class="btn btn-sm btn-link" target="_blank" href="'.backpack_url("chapters/". $this->slug).'" data-toggle="tooltip" title="Danh sách chapter"><i class="la la-book"></i> Danh sách chapter</a>';
    }

    public function totalViews()
    {
      $totalViews = intval($this->views()->sum('views'));

    // if ($totalViews >= 1_000_000) {
    //     $millions = round($totalViews / 1_000_000, 1);
    //     return "{$millions}M"; 
    // } else {
    //     return number_format($totalViews, 0, '', '.');
    // }
    return number_format($totalViews, 0, '', '.');
    }

    public function getType()
    {
      return $this->types()->first();
    }

    public function getStatus()
    {
        return $this->statuses()->first();
    }

    // protected function titlePattern(): string
    // {
    //     return Setting::get('site_movie_title', '');
    // }

    public function crawlChapterButton(){
        $manga_slug = $this->slug;
        return '<a class="btn btn-sm btn-link" href="'.backpack_url("manga/crawl_chapter/{$manga_slug}").'"><i class="la la-download"></i> Tải chapter</a>';
    }

    public function getCoverAttribute(){
        $image_url = $this->attributes['cover'];
        if (strpos($image_url, 'kcgsbok.com/nettruyen') !== false
            || strpos($image_url, 'ddntcthcd.com/nettruyen') !== false
            || strpos($image_url, 'cmnvymn.com/nettruyen') !== false) {
            return route('get-bp-image', [
                'referrer' => config('custom.nettruyen_domain'), 
                'image_url' => $image_url
            ]);
        }
        
        if (strpos($image_url, 'https://imgthumb.giatot.xyz/image_thumbs/') !== false) {
            $image_url = str_replace(
            "https://imgthumb.giatot.xyz/image_thumbs/", 
            "https://kcgsbok.com/nettruyen/thumb/", 
            $image_url
        );

        // Replace the file extension
        $image_url = str_replace("_thumb.webp", ".jpg", $image_url);
        return route('get-bp-image', [
                'referrer' => config('custom.nettruyen_domain'), 
                'image_url' => $image_url
            ]);
        }
        
        return $image_url;
    }


    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
