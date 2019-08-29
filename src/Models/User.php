<?php

namespace Cochlea\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $uid
 * @property string $username
 * @property string $email
 */
class MyBBUsers extends Model {
    public const tableName = 'users';
    protected $table = self::tableName;
    protected $guarded = ["uid"];
    protected $primaryKey = 'uid';
    public $timestamps = false;

    public function userGroup() {
        return $this->hasOne(MyBBUserGroup::class, 'gid', 'usergroup');
    }
}