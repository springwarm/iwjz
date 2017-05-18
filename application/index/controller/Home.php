<?php
/**
 * 网站首页控制器
 * Created by PhpStorm.
 * User: ameng
 * Date: 2017/4/18
 * Time: 14:49
 */

namespace app\index\controller;


use app\common\controller\Base;
use app\common\model\AccountLog;
use app\common\model\SysUser;
use app\common\service\AccountLogService;
use app\common\service\UserService;
use think\Config;
use think\console\Input;
use think\Controller;
use think\Exception;
use think\Request;
use think\Response;
use think\Session;
use tool\MCore;

class Home extends Base
{

    public function index ()
    {
        $this->page_title       = '流水明细';

        $request                = Request::instance();
        $options['open_id']     = $this->_open_id;
        $options['user_id']     = $this->user_id;
        $options['time_from']   = $request->param('time_from', date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")))); // 默认本月第一天
        $options['time_to']     = $request->param('time_to',   date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y")))); // 默认本月第二天
        $options['sort_id']     = $request->param('sort_id',-1);
        $options['tag']         = $request->param('tag',    '');
        $order_by               = $request->param('order_by',      []);
        $page                   = 0;
        $limit                  = 5;
        // 本月流水
        $log_info               = AccountLogService::getAccountLogList($options, $order_by, $page, $limit);
        // 汇总统计
        $log_info['statistics'] = AccountLogService::getStatisticsInfo($options);

        // 用户信息

        $log_info['user_info']  = SysUser::getUserInfo($this->user_id);

        $log_info['greeting']   = UserService::getGreetingInfo();

        return $this->fetch('', $log_info);
    }

    /**
     * 删除记录
     * @return string
     */
    public function del ()
    {

        try {
            $requset    = Request::instance();
            if (!$requset->isAjax()) {
                MCore::clientException(4001, '非法访问');
            }

            $log_id     = (int)$requset->post('log_id');
            if (empty($log_id)) {
                MCore::clientException(4002, '请选择删除项');
            }

            $ret    = AccountLog::deleteLog($log_id, $this->user_id);
            if (!$ret) {
                MCore::serviceException(5001, '删除失败!');
            }

        } catch (Exception $e) {
            $this->ajaxReturn($e->getCode(),$e->getMessage(),'');

        }

        $this->ajaxReturn(0,'删除成功!');

    }







}