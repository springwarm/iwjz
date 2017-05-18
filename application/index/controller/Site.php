<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/18
 * Time: 23:40
 */
namespace app\index\controller;

use app\common\controller\Base;
use app\common\model\SysUser;
use think\Exception;
use think\Request;
use think\Session;
use tool\MCore;

class Site extends Base
{
	public function login ()
	{
		$this->page_title   = '登录';
		$request            = Request::instance();
		$session            = $request->session();
		$this->_open_id     = $request->get('openid');

        // 登录后再进登录页面则会自动跳到首页
		if(isset($session['openid']) && !empty($session['openid'])) {
			$this->redirect('/index/home/index');
		}

		if ($request->isAjax()) {
			try {
				$pwd            = $request->param('password');
				if (empty($this->_open_id) && empty($request->session('openid'))) {
					MCore::clientException('非法访问，请在订阅号输入m，从网站入口进入。',1);
				}
				if (empty($pwd)) {
					MCore::clientException('请输入密码！', 2);
				}

				$info       = SysUser::getInfoByLogin($this->_open_id, $pwd);
				if (empty($info)) {
					MCore::clientException('密码错误！', 3);
				}
				Session::set('openid',      $info['openid']);
				Session::set('user_id',     $info['user_id']);
				Session::set('nickname',    $info['nickname']);
				Session::set('mobilephone', $info['mobilephone']);
				Session::set('email',       $info['email']);
				$this->ajaxReturn(0,'');
			} catch (Exception $e){
				$this->error($e->getMessage());

			}
		}
		$this->view->engine->layout('nomain_layout');
		return $this->fetch();
	}

	public function logout ()
    {
        $open_id    = Session::get('openid');
        Session::set('openid',      '');
        Session::set('user_id',     '');
        Session::set('nickname',    '');
        Session::set('mobilephone', '');
        $this->ajaxReturn(0,'退出成功!',$open_id);
    }



}