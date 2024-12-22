<?php

namespace Ophim\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class View extends Model
{
  use HasFactory;

  protected $fillable = ["model", "key", "views", "created_at"];

  public static function getTotalViews(){
    return self::sum('views');
  }
}
