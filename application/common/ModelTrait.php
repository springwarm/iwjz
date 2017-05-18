<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/23
 * Time: 11:26
 */

namespace app\common;
use think\Db;

trait ModelTrait
{
	/** 判断是否存在
	 * @param $table
	 * @param $condition
	 * @return bool
	 */
	public static function isExists ($table, $condition)
	{
		$sql    = "SELECT 1 FROM {$table} WHERE $condition LIMIT 1";
		return (bool)Db::query($sql);

	}


}