<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/16
 * Time: 18:53
 */
namespace app\common\service;


use app\common\model\SysUser;
use think\Exception;

class UserService
{
	/**
	 * 对微信用户进行信息初始化
	 * @param $wxuser_info
	 * @return bool|false|int
	 */
	public static function handleWxUser ($wxuser_info)
	{
		$user_id    = SysUser::isExistUser($wxuser_info['open_id']);
		if (!$user_id) {
			$model              = new SysUser();
			$model->user_name   = self::genUserNumber();
			$model->original_pwd= self::getRandStr(10);
			$model->user_pwd    = password_hash($model->original_pwd,PASSWORD_DEFAULT);
			$model->nickname    = self::genUserNumber(6);
			$model->openid      = $wxuser_info['open_id'];
			$ret                = $model->save();
			if (!$ret) {
			    throw new Exception('用户更新失败', 40002);
            }
		}
		return $user_id ;
	}

	/**
	 * 随机生成用户名
	 * @return string
	 */
	private static function genUserNumber($lenght = 6)
	{
		$chars = "0123456789abdefghijkmnpqrstvwxy";
		$username = "";
		for ( $i = 0; $i < $lenght; $i++ )
		{
			$username .= $chars[mt_rand(0, strlen($chars))];
		}
		return strtoupper(base_convert(time() - 1420070400, 10, 36)).$username;
	}


	/**
	 * 产生随机字符串
	 *
	 * 产生一个指定长度的随机字符串,并返回给用户
	 *
	 * @access public
	 * @param int $len 产生字符串的位数
	 * @return string
	 */
	private static function getRandStr($len=6) {
		$chars='ABDEFGHJKLMNPQRSTVWXYabdefghijkmnpqrstvwxy23456789#%*'; // characters to build the password from
		mt_srand((double)microtime()*1000000*getmypid()); // seed the random number generater (must be done)
		$password='';
		while(strlen($password)<$len)
			$password.=substr($chars,(mt_rand()%strlen($chars)),1);
		return $password;
	}


	public static function getGreetingInfo ()
	{
		date_default_timezone_set('Asia/Shanghai');
		$h      =   date("H");
		if ($h < 11) {
			$info = '早上好!';
		} else if($h<13) {
			$info = '中午好！';
		} else if($h<17) {
			$info = '下午好！';
		} else {
			$info = '晚上好！';
		}

		return $info;
	}
}