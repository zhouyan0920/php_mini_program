<?php

namespace app\admin\model;

use think\Model;

class Comments extends Model
{
    protected $table = 'ssp_comments';
    public function images(){
        return $this->hasMany('Images','ssp_comment_id','id');
    }


}
