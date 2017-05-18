<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/18
 * Time: 2:51
 */

namespace app\index\controller;

use app\common\controller\Base;
use app\common\service\AccountLogService;
use think\Exception;
use think\Request;
use tool\MCore;

class AccountLog extends Base
{
	public function logList ()
	{

		return $this->fetch('log_list');
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
            $ret    = \app\common\model\AccountLog::deleteLog($log_id, $this->user_id);
            if (!$ret) {
                MCore::serviceException(5001, '删除失败!');
            }

        } catch (Exception $e) {
            $this->ajaxReturn(-1,$e->getMessage(),'');

        }

        $this->ajaxReturn(0,'删除成功!');

    }

    // 增加--记一笔
    public function add ()
    {
        $requset    = Request::instance();
        $data       = $requset->post();
        $data['log_title']  = '网站录入';
        $data['log_date']   = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $data['user_id']    = $this->user_id;
        $ret        = \app\common\model\AccountLog::addLog($data);
        if ($ret) {
            $this->ajaxReturn(0, '添加成功');
        } else {
            $this->ajaxReturn(-1,'添加失败!');
        }

    }

    // TODO
    public function edit ()
    {

    }

    public function ajax_list ()
    {
        $request = Request::instance();
        $page    = (int)$request->post('page',0);
        $limit   = (int)$request->post('count');
        $limit   = empty($limit)? 5: $limit;
        $data['user_id']    = $this->user_id;
        $log_info    = AccountLogService::getAccountLogList($data,[],$page,$limit);

        if (!isset($log_info['list']) || empty($log_info['list'])) {
            $this->ajaxReturn(-1,'没有数据可加载');
        }


        $this->ajaxReturn(0, '', $log_info);
    }

    public function ajax_statistics ()
    {
        // 1 获取并检验输入
        $request = Request::instance();
        $query_date = $request->param('query_date');
        if (!$query_date) {
            $this->ajaxReturn(-1, '查询日期不能为空!', '');
        }
        $date_arr   = explode(' ', $query_date); // 格式：  年份 月份
        if (!isset($date_arr[1])) { // 这里用isset()比用count()有效率
            $this->ajaxReturn(-2, '格式错误');
        }

        // 2 获取指定日期的数据
        $statistics     = AccountLogService::getQueryStatisticsInfo($date_arr[0], $date_arr[1], $this->user_id);

        // 3 获得html模板
        $cart_info      = AccountLogService::getCartTemplate($statistics);

        $this->ajaxReturn(0,'',$cart_info);

    }

}