<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/17
 * Time: 2:01
 */
namespace app\common\model;

use think\Model;

class Sort extends Model
{

    public static function getSortId ($value , $type = 1)
    {
        $field  = '';
        $op     = '';
        switch ($type) {
            case 1 :
                $field  = 'sort_name';
                $op     = 'like';
                $value  = '%'.$value.'%';

        }

        return self::find()->where($field,$op,$value)->value('sort_id');

    }

    /**
     * 获取分类信息
     * @param $value 条件值
     * @param int $type 条件类型
     * @return array|false|\PDOStatement|string|Model
     */
    public static function getSortInfo ($value,  $type = 1)
    {
        $field  = '';
        $op     = '';
        switch ($type) {
            case 1 :
                $field  = 'sort_name';
                $op     = 'like';
                $value  = '%'.$value.'%';

        }
        $model  = new self();
        return $model->where($field,$op,$value)->find();

    }
    public static function getSortNameColumn ()
    {
        $model  = new self();
        $column = $model->column('sort_name');
        return $column;

    }
    public static function getSortIdColumn ()
    {
        $model  = new self();
        $column = $model->column('sort_id');
        return $column;

    }
    public static function getMap ($field)
    {
        $model  = new self();
        return $model->column("sort_id, {$field}");

    }
}