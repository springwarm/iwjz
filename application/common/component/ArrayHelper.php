<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/18
 * Time: 22:18
 */
namespace app\common\component;

class ArrayHelper
{

	/**
	 * 判断是不是索引数组/列表/向量表
	 * @param array $array
	 * @return bool
	 */
	public static function is_assoc(array $array) {
		if(is_array($array)) {
			$keys = array_keys($array);
			return $keys != array_keys($keys);
		}
		return false;
	}



}