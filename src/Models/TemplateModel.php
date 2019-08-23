<?php

namespace Cochlea\Models;

use Illuminate\Database\Eloquent\Model;

class MyBBTemplates extends Model {
    protected $table = "templates";
    protected $guarded = ["id"];
    public $timestamps = false;
}