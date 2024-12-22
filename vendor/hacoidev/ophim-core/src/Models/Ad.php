<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Traits\HasFactory;

class Ad extends Model implements Cacheable
{
    use CrudTrait;
    use HasFactory;
    use HasCache;

    protected $fillable = [
        'name',
        'identifier',
        'description',
        'image',
        'link',
        'script',
        'type',
        'is_active',
    ];

    // public function views()
    // {
    //     return $this->hasMany(View::class, 'key')->where('model', self::class);
    // }

    // public static function getTotalViews()
    // {
    //     return self::with('views')->get()->sum(function ($chapter) {
    //         return $chapter->views->sum('views');
    //     });
    // }
}
