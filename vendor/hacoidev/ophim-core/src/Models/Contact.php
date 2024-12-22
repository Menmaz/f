<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Traits\HasFactory;

class Contact extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'contacts';
    protected $primaryKey = 'id';

    protected $fillable = ["email", "subject", "message", 'created_at', 'updated_at'];


}
