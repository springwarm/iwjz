<?php
/**
 * TraceHttpLog.php
 * Created by PhpStorm.
 * User: ameng
 * Date: 2017/4/17
 */
namespace app\common\model;
use think\Model;

class TraceHttpLog extends Model
{

    public static function trace ()
    {
        $model  = new self();
        $model->remote_addr = $_SERVER['REMOTE_ADDR'];
        $model->query_string= (string)$_SERVER['REQUEST_URI'];
        $model->query_time  = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        return $model->save();
    }
}