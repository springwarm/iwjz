<?php
/**
 * AccountLogService.php
 * Created by PhpStorm.
 * User: ameng
 * Date: 2017/4/19
 * Time: 9:57
 */
namespace app\common\service;

use app\common\model\AccountLog;
use app\common\model\Sort;
use think\Db;
use think\Validate;
use tool\MCore;

class AccountLogService
{
    static public $table  = 'account_log';
    public static function getAccountLogList ($options, $order_by = [], $page = 1, $limit = 10)
    {
        if (!isset($options['user_id']) && empty($options['user_id'])) {
            MCore::clientException('非法访问', 1);
        }
        $where                  = ' user_id = :user_id '; // 默认条件
        $params['user_id']      = $options['user_id'];    // 预编译变量
        $verify_data['user_id'] = 'number';               // 验证条件
        // 处理查询条件
        self::handleQueryTerms($options, $where, $params, $verify_data);

        // 处理排序
        $order   = ' acc_date DESC ';
        if (!empty($order_by)) {
            // TODO
        }

        // 进行数据验证
        $validator  = new Validate();
        if (!$validator->check($verify_data)) {
            MCore::clientException($validator->getError(), 1);
        }


        $fields     = '*';
        $table      = self::$table;
        $data_total = "SELECT count(*) as total FROM {$table} WHERE {$where}";
        $total      = Db::query($data_total, $params);
        $total      = $total[0]['total'];
        if ($total == 0) {
            return ['list'=>[], 'total'=>0, 'page'=>1, 'count'=>$limit, 'is_next'=>false];
        }

        $pinfo      = MCore::getPagination($page, $limit, $total);
        $list_sql   = "SELECT {$fields} FROM {$table} WHERE {$where} ORDER BY {$order} LIMIT {$pinfo['offset']}, {$limit}";
        $list       = Db::query($list_sql, $params);
        if (!empty($list)) {
            $sort_map  = Sort::getMap('sort_name');
            $sort_icon_map  = Sort::getMap('icon');
            foreach ($list as $k => $v) {
                $list[$k]['acc_direction_name'] = $v['acc_direction']? '收入': '支出';
                $list[$k]['sort_name']          = $sort_map[$v['sort_id']];
                $list[$k]['icon']               = $sort_icon_map[$v['sort_id']];
                $list[$k]['inner_content']      = "类目:{$sort_map[$v['sort_id']]}|数额:{$v['acc_amount']}元|标签:{$v['tag']}";
            }
        }else {
            $list   = [];
        }

        return ['list'=>$list, 'total'=>$total, 'page'=>$pinfo['page'], 'count'=>$limit, 'is_next'=>$pinfo['is_next']];

    }

    /**
     * @param $options
     * $options = [
     *  'user_id'=>'',
     * ];
     * @return array
     */
    public static function getStatisticsInfo ($options)
    {
        $statistics_info    = [];
        $sort_ids           = Sort::getMap('sort_name,icon');
        // 本月总开销

        //php获取本月开始时间戳和结束时间戳
        $this_month_start   = mktime(0,0,0,date('m'),1,date('Y'));
        $this_month_end     = mktime(23,59,59,date('m'),date('t'),date('Y'));
        $this_month_list    = AccountLog::getLogListByTime($options['user_id'],$this_month_start,$this_month_end);
        $statistics_info['this_month_statis']    = self::handleCountLog($this_month_list,$sort_ids);
        $statistics_info['this_month_statis']['date']   = date('Y-m');
        //php获取上月起始时间戳和结束时间戳
        $pre_month_start                    = mktime(0, 0 , 0,date("m")-1,1,date("Y"));
        $pre_month_end                      = mktime(23,59,59,date("m") ,0,date("Y"));
        $pre_month_list                     = AccountLog::getLogListByTime($options['user_id'],$pre_month_start,$pre_month_end);
        $statistics_info['pre_month_statis']= self::handleCountLog($pre_month_list,$sort_ids);
        $statistics_info['pre_month_statis']['date']   = date('Y').'-'.(date('m')-1);

        //php获取今年数据
        $this_year_start                    = mktime(0,0,0,1,1,date('Y'));
        $this_year_end                      = mktime(23,59,59,12,31,date('Y'));
        $this_year_list                     = AccountLog::getLogListByTime($options['user_id'],$this_year_start,$this_year_end);
        $statistics_info['this_year_statis']= self::handleCountLog($this_year_list,$sort_ids);
        $statistics_info['this_year_statis']['date']   = date('Y').'年';
        return $statistics_info;

    }

    /**
     * 处理查询条件
     * @param $where
     * @param $params
     * @param $verify_data
     */
    public static function handleQueryTerms($options,&$where, &$params, &$verify_data)
    {
        if (isset($options['time_from']) && !empty($options['time_from'])) {
            $where                      .= ' AND acc_date > :time_from ';
            $params['time_from']        = $options['time_from'];
            $verify_data['time_from']   = 'date';
        }

        if (isset($options['time_to']) && !empty($options['time_to'])) {
            $where                  .= ' AND acc_date < :time_to ';
            $params['time_to']      = $options['time_to'];
            $verify_data['time_to'] = 'date';
        }

        if (isset($options['sort_id']) && $options['sort_id'] != -1) {
            $where                  .= ' AND sort_id = :sort_id ';
            $params['sort_id']      = $options['sort_id'];
            $verify_data['sort_id'] = 'number';
        }

        if (isset($options['tag']) &&  !empty($options['tag'])) {
            $where                  .= ' AND tag like :tag ';
            $params['sort_id']      = '%'.$options['sort_id'].'%';
        }
    }

    public static function handleCountLog ($log_list, $sort_ids)
    {
        $sort_statis['list']    = [];
        foreach ($sort_ids as $id => $value) {
            $sort_statis['list'][$id]['sort_name']   = $value['sort_name'];
            $sort_statis['list'][$id]['money']   = 0;
            $sort_statis['list'][$id]['icon']        = $value['icon'];
        }
        $sort_statis['total_pay']    = 0;// 总支出
        $sort_statis['total_income'] = 0;// 总收入
        $sort_statis['total_fee']    = 0;// 净收支
        if ($log_list) {
            foreach ($log_list as $key => $list) {
                if (!isset($sort_statis['list'][$list['sort_id']])) {
                    $sort_statis['list'][$list['sort_id']]['money']  = 0;
                }
                // 统计餐饮
                if (in_array($list['sort_id'],[1,5,6,7,8],true)) {
                    if ($list['sort_id'] == 1 ) {
                        $sort_statis['list'][1]['money'] += $list['acc_amount'];
                    } else {
                        $sort_statis['list'][1]['money'] += $list['acc_amount'];
                        $sort_statis['list'][$list['sort_id']]['money'] += $list['acc_amount'];
                    }

                } else {
                    $sort_statis['list'][$list['sort_id']]['money']    += $list['acc_amount'];

                }

                if ($list['acc_direction']!=1) {
                    $sort_statis['total_pay']   += $list['acc_amount']; // 总支出
                } else {
                    $sort_statis['total_income']+= $list['acc_amount']; // 总收入
                }

                $sort_statis['total_fee']   = $sort_statis['total_income'] - $sort_statis['total_pay'];// 净收支，这里命名不是很准确，谅解。

            }

        }
        return $sort_statis;
    }

    public static function getQueryStatisticsInfo ($year, $month, $user_id)
    {
        $statistics_info    = [];
        $sort_ids           = Sort::getMap('sort_name,icon');
        unset($sort_ids[1]); // 去除餐饮类目
        unset($sort_ids[11]);

        if ($month == 0) {
            $this_date_start                    = mktime(0,0,0,1,1,$year);
            $this_date_end                      = mktime(23,59,59,12,31,$year);
            $this_date_list                     = AccountLog::getLogListByTime($user_id,$this_date_start,$this_date_end);
            $statistics_info                    = self::handleCountLog($this_date_list, $sort_ids);
            $statistics_info['date']            = $year.'年';
        } else {
            $this_date_start                    = mktime(0,0,0,$month,1,$year);
            $this_date_end                      = mktime(23,59,59,$month,date('t'),$year);
            $this_date_list                     = AccountLog::getLogListByTime($user_id,$this_date_start,$this_date_end);
            $statistics_info                    = self::handleCountLog($this_date_list,$sort_ids);
            $statistics_info['date']            = $year.'年'.$month.'月';
        }
        return $statistics_info;

    }

    public static function getCartTemplate ($statistics)
    {
        $cart_head   = " <div class=\"card-header\">支出详情:</div>";
        $cart_list   = '';
        foreach ($statistics['list'] as $key => $value) {
            $temp   = "<li class=\"item-content\"><div class=\"item-media\"><svg class=\"icon icon_cart\" aria-hidden=\"true\" width=\"44px\" height=\"44\">"
                ."<use xlink:href=\"{$value['icon']}\"></use></svg></div>"
                ."<div class=\"item-inner\"><div class=\"item-title-row\"><div class=\"item-title\">{$value['sort_name']}</div>"
                ."</div><div class=\"item-subtitle\">{$value['money']}元</div></div></li>";
            $cart_list  .= $temp;
        }
        $cart_footer    = "<div class=\"card-footer\">"
            ."<span>{$statistics['date']}</span>"
            ."<span>总支出 {$statistics['total_fee']} 元</span>";
        return $cart_head.$cart_list.$cart_footer;
    }

}