<?php

namespace Cochlea\Models;

use Illuminate\Database\Eloquent\Model;

class MyBBTemplates extends Model {
    protected $table = "templates";
    protected $guarded = ["tid"];
    public $timestamps = false;
    protected $primaryKey = 'tid';
}