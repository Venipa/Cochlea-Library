<?php

namespace Cochlea\Models;

use Illuminate\Database\Eloquent\Model;

class MyBBTemplates extends Model {
    protected $table = "templates";
    protected $guarded = ["tid"];
    public $timestamps = false;
    protected $primaryKey = 'tid';
    public function makeTemplate(...$args) {
        return self::parseTemplate($this->template, $args);
    }
    public static function parseTemplate($template, ...$args) {
        foreach($args as $key => $value) {
            $template = str_replace("{".$key."}", $value, $template);
        }
        return $template;
    }
}