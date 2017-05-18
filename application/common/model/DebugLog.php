<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/16
 * Time: 18:10
 */
namespace app\common\model;
use think\Model;

class DebugLog extends Model
{
	protected $pk       = 'uid';
	protected $table    = 'debug_log';



	public static function Debug ()
	{
	}
}
