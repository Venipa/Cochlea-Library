<?php

namespace Cochlea\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $gid
 * @property string $title
 * @property string $description
 * @property string $usertitle
 * @property int $stars
 * @property bool $isbannedgroup
 */
class MyBBUserGroup extends Model {
    public const tableName = 'usergroups';
    protected $table = self::tableName;
    protected $guarded = ["gid"];
    protected $primaryKey = 'gid';
    public $timestamps = false;

    public function users() {
        return $this->hasMany(MyBBUsers::class, 'usergroup', 'gid');
    }
}