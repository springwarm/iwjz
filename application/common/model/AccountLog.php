<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/17
 * Time: 2:01
 */
namespace app\common\model;

use think\Model;
use app\common\ModelTrait;


class AccountLog extends Model
{
    public static function createRecord ()
    {
        $model   = new self();
        $model->log_title = '';

    }

    public static function rowsNum ($id)
    {
        return self::find()->where('log_id','=',$id)->count();
    }

    public static function deleteLog ($id, $user_id)
    {
        $model  = new self();
        return $model->where('log_id', '=', $id)->where('user_id','=',$user_id)->delete();
    }

    public static function addLog ($data)
    {
        $params['log_title']    = $data['log_title'];
        $params['log_date']     = $data['log_date'];
        $params['acc_date']     = $data['acc_date'];
        $params['sort_id']      = (int)$data['sort_id'];
        $params['acc_amount']   = $data['acc_amount'];
        $params['acc_direction']= (int)$data['acc_direction'];
        $params['tag']          = isset($data['tag'])? $data['tag']: '';
        $params['log_memo']     = isset($data['log_memo'])? $data['log_memo']: '';
        $params['user_id']      = $data['user_id'];
        $sql    = 'INSERT INTO account_log(log_title,log_date,acc_date,sort_id,acc_amount,acc_direction,tag,log_memo,user_id) VALUES (:log_title,:log_date,:acc_date,:sort_id,:acc_amount,:acc_direction,:tag,:log_memo,:user_id)';
        $ret    = self::execute($sql, $params);
        if (!$ret) {
            logging(self::getError());
        }
        return self::getLastInsID();

    }


    public static function getLogList ($user_id)
    {
        $model  = new self;
        return $model->where('user_id', '=', $user_id)->select();
    }

    public static function getLogListByTime ($user_id, $time_start , $time_end)
    {
        $model  = new self;
        return $model->where('user_id','=',$user_id)->where('acc_date','between time',[$time_start,$time_end])->select();
    }

    /**
     * 是否存在该流水记录
     * @param $id 记录ID
     * @return bool
     */
    public static function isExists ($id)
    {
        $condition  = 'log_id = '.(int)$id;
        return ModelTrait::isExists('account_log',$condition);
    }

}