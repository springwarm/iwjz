<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/16
 * Time: 18:43
 */
namespace app\common\model;

use think\Model;

class SysUser extends Model
{

	public static function isExistUser ($id, $type = 1)
	{
	    $field  = '';
		switch ($type) {
			case 1 : // open_id 查询
				$field  = 'openid';
				break;
		}
		$model  = new self();
		return $model->where($field, '=', $id)->value('user_id');

	}

	public static function getInfoByLogin ($open_id, $password)
	{
		$model  = new self;
		$info   =  $model->where('openid','=',$open_id)->field('user_id,openid,user_name,user_pwd,nickname,mobilephone,qq,email')->find()->toArray();
		if (empty($info)) {
			return false;
		}
		if ( !password_verify($password, $info['user_pwd'])) {
			return false;
		}
		unset($info['user_pwd']);
		return $info;

	}

    /**
     * 根据条件获取某字段值
     * @param $field
     * @param $id
     * @param int $type
     * @return mixed
     */
	public static function getScalar ($field, $id, $type = 1)
    {
        $condition  = '';
        switch ($type) {
            case 1 : // open_id 查询
                $condition  = 'openid';
                break;
        }
        $model  = new self();
        return $model->where($condition, '=', $id)->value($field);
    }

	public static function getUserInfo ($id, $type = 1)
	{
		$condition  = '';
		switch ($type) {
			case 1 :
				$condition  = 'user_id';
				break;
		}
		$model  = new self;
		return $model->field('user_id,openid,user_name,nickname,mobilephone,qq,email')->where($condition,'=',$id)->find();

	}


}