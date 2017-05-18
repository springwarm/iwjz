<?php
/**
 * 微信信息类型的处理
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/16
 * Time: 23:37
 */
namespace app\common\service;

use app\common\model\AccountLog;
use app\common\model\Sort;
use app\common\model\SysUser;
use app\common\service\UserService;
use tool\MCore;
use think\Cache;
use think\Exception;
use wechat\TPWechat;
use wechat\Wechat;
use Think\Validate;


class WechatService
{
	private static $_instance;
	private $_expressions    = [ // 正则表达式数组
		'jl'=>'/(\D+)([\d\.?]+)(\D.+)?/',// 类目/数目/备注   比如  早餐10汤粉

	];
	private $_actions   = [ // 动作
		'bdsj'=>'mobilephone',//绑定手机号
		'bdyx'=>'email',//绑定邮箱
		'bdqq'=>'qq',//绑定QQ
		'xgmm'=>'user_pwd',//修改密码
		'cxjl'=>'',//撤销记录

	];
	private $_msg_type;
	private $_wechat_obj;
	private $_open_id;
	private $_cache_jl_key = ''; // 上一次的记录缓存键名
	private $_user_id = 0;
	private $_sort_names = '';
	private $_menu_template = '该操作无法识别，<a href = "http://mp.weixin.qq.com/s/qqShuDgriWL7SYUc0I8o0Q">使用说明</a>'
	.'|<a href="http://iwjz.windfly.top/index/home/index">进入网站</a>';

	private $_command_ret    = ''; // 操作成功返回值

	private function __construct($options)
	{
		$this->_wechat_obj = new TPWechat($options);
		//$this->_wechat_obj->valid();//明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
		$this->_msg_type    = $this->_wechat_obj ->getRev()->getRevType();
		$this->_open_id     = $this->_wechat_obj ->getOpenId();
		if (empty($this->_open_id)) {
			MCore::clientException('非法访问，请公众号后台留言');
		}
		// 订阅号无法获取授权TOKEN
		//$this->_wechat_obj ->getAccessToken( 'wxd7849d38df7bddf3', '1fab04b5d715634301023a1e143ca9b6');
		//$wxuser_info            = $this->_wechat_obj ->getUserInfo($this->_open_id);
		$wxuser_info['open_id'] = $this->_open_id;
		$this->_cache_jl_key    = $this->_open_id.'_jl';
		$this->_user_id         = UserService::handleWxUser($wxuser_info);
		$this->_sort_names      = implode(',', Sort::getSortNameColumn());

		// 菜单模板
		$this->_menu_template   = '<a href = "http://mp.weixin.qq.com/s/qqShuDgriWL7SYUc0I8o0Q">使用说明</a>';// 公众号文章详情地址
		$this->_menu_template   .= "|<a href=\"http://iwjz.windfly.top/index/home/index?openid={$this->_open_id}\">我的记账本</a>";

	}

	private function __clone()
	{
		// TODO: Implement __clone() method.
	}


	public static function getInstance ($options)
	{
		if (is_null(self::$_instance)|| !isset(self::$_instance)) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}

	public function replyInfo ()
	{
		try {
			switch($this->_msg_type) {
				case Wechat::MSGTYPE_TEXT: // 处理文本信息
					$content    = $this->_wechat_obj->getRevContent();
					// 进行逻辑处理
					$response   = $this->_opByContent($content);
					$this->_wechat_obj ->text($response)->reply();
					exit;

				case Wechat::MSGTYPE_EVENT: // 处理事件
					$event_info = $this->_wechat_obj->getRevEvent();
					$response   = self::getEventResText($event_info['event']);
					$this->_wechat_obj->text($response)->reply();
					break;
				case Wechat::MSGTYPE_IMAGE:
					break;
				default:
					$this->_wechat_obj ->text("help info")->reply();
			}
		} catch (Exception $e) {
			logging($e->getMessage());
			$msg    = $this->_wechat_obj->debug?$e->getMessage():'系统错误，请公众号后台留言!';
			$this->_wechat_obj->text($msg)->reply();
		}
	}

	/**
	 * 根据文本内容来进行对应操作
	 * @param $content
	 */
	private  function _opByContent ($content)
	{
		// 优先处理操作类型
		if ($this->_handleCommand($content)) {
			$result = $this->_command_ret;
		} elseif ($this->_handleAction($content)) {
			$result = '操作成功!';
		} elseif ($this->_handleRecord($content)) {
			$result = '记录成功';
		} else {
			$result = false;
		}
		$result = ($result !== false)? $result: '系统无法识别,'.$this->_menu_template;
		return $result;
	}

	private function _handleAction($content)
	{
		$result    = false;

		foreach ($this->_actions as $action => $field) {
			if (strstr($content, $action)) {
				$model  = new SysUser();
				$value  = substr($content,4);
				$rule   = [
					['qq','^[1-9]\d{4,10}$','qq号码错误'],
					['mobilephone','/1[34578]{1}\d{9}$/','手机号错误'],
					['email','email','email格式错误'],
				];
				$result = $model->validate($rule)->save([$field=>$value],['openid'=>$this->_open_id]);
				if (!$result) {
					$msg = is_array($model->getError())?json_encode($model->getError()):$model->getError();
					MCore::clientException($msg, 6);

				}

			}
		}
		return $result;
	}

	private function _handleRecord ($content)
	{
		$ret        = false;
		foreach ($this->_expressions as $key => $pattern) {
			preg_match_all($pattern, $content, $temp);
			if (!isset($temp[1][0])||!isset($temp[2][0]) || empty($temp[1][0]) || empty($temp[2][0])) {
				continue;
			}
			if ($key == 'jl') {
				$sort_name  = $temp[1][0];
				$acc_amount = $temp[2][0];
				$log_memo   = $content;
				// 获取分类ID
				$sort_info    = Sort::getSortInfo($sort_name);
				if (empty($sort_info)) {
					MCore::clientException('类目不存在!系统类目仅支持:'."\n".$this->_sort_names);
				}
				// 入账记录
				$time   = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);;
				$log_data   = [
					'log_title'     => '公众号录入',
					'log_date'      => $time,
					'acc_date'      => $time,
					'sort_id'       => $sort_info['sort_id'],
					'acc_amount'    => $acc_amount,
					'acc_direction' => $sort_info['sort_direction'],
					'log_memo'      => $log_memo,
					'user_id'       => $this->_user_id,
					'tag'           => isset($temp[3][0])?$temp[3][0]:''
				];
				$jl_id  = AccountLog::addLog($log_data);
				if (empty($jl_id)) {
					MCore::serviceException('入账失败!请公众号后台留言');
				}

				// 记录此次入账记录ID
				$cache      = Cache::connect();
				$set_ret    = $cache->set($this->_cache_jl_key,$jl_id);
				if (!$set_ret) {
					logging("jl_error::user_id:[{$this->_user_id}]|jl_id:[{$jl_id}]");
				}
				return true;
			}
		}


		return $ret;
	}

	/**
	 * 处理单命令
	 * @param $content
	 * @return bool
	 * @throws Exception
	 */
	private function _handleCommand ($content)
	{
		$result = true;
		switch (trim(strtolower($content))) {
			case 'cxjl' : // 撤销上一次记录
				$cache  = Cache::connect();
				$jl_id  = $cache->get($this->_cache_jl_key);

				if (empty($jl_id)) {
					MCore::clientException('撤销失败,系统并没有上一次记录',1);

				}
				// 删除该记录
				$is_exists  = AccountLog::isExists($jl_id);
				//$is_exists  = (float)AccountLog::rowsNum($jl_id);
				if (!$is_exists) {
					logging('记录异常,系统查询不到此次记录|'.$jl_id);
					MCore::serviceException('记录异常,系统查询不到此次记录',1);
				}
				$del_ret = AccountLog::deleteLog($jl_id, $this->_user_id);
				if (!$del_ret) {
					$cache->set($this->_cache_jl_key, $jl_id);
					logging($this->_cache_jl_key.'|'.$jl_id,'error');
					MCore::serviceException('删除失败!',2);

				}
				// 删除成功则清空上一次记录ID
				$cache->set($this->_cache_jl_key, 0);
				$this->_command_ret = '删除成功!';
				break;

			case 'm' :
				$this->_command_ret = $this->_menu_template;
				break;

			case 'h' :
				$this->_command_ret = '<a href = "http://mp.weixin.qq.com/s/qqShuDgriWL7SYUc0I8o0Q">查看简介</a>';
				break;

			case 'csmm' :
				$pwd                = SysUser::getScalar('original_pwd', $this->_open_id, 1);
				$this->_command_ret  = $pwd? $pwd: '无初始密码，请公众号后台留言';
				break;

			default :
				$result = false;
				break;
		}

		return $result;

	}



	/**
	 * 根据事件类型返回文本
	 * @param $event_type
	 * @return string
	 */
	public static function getEventResText ($event_type)
	{
		$content    = '';
		switch ($event_type) {
			case 'subscribe' :
				$content    = 'Hey,欢迎使用i微随身记,帮你随时随身记!'."\n";
				$content    .= '- - - - - - - - - - - - - - -'."\n";
				$content    .= '1.支持文字记账,格式: 类目金额标签，如:早餐10汤粉'."\n";
				$content    .= '2.支持信息绑定,信息:qq，手机号，邮箱'."\n";
				$content    .= '3.支持excel报表生成'."\n";
				$content    .= '4.支持上一笔记账明细的撤销'."\n";
				$content    .= 'N.后面还会有更多功能陆续推出, 敬请期待!';
				$content    .= '- - - - - - - - - - - - - - -'."\n";
				$content    .= '文字发送m，召唤菜单!文字发送h，可查看最新公告!';
				break;

			case 'unsubscribe' :
				break;
		}
		return $content;
	}


}