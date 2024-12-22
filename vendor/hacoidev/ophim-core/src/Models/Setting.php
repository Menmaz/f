<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Traits\HasFactory;

class Setting extends Model implements Cacheable
{
    use CrudTrait;
    use HasFactory;
    use HasCache;

    protected $table = 'settings';

}
