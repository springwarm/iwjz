<?php
namespace app\common\component;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
class ManageException extends Handle
{

    public function render(Exception $e)
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
        }
        if (!is_null($error = error_get_last())) { // 记录错误
            logging($error['file'].'-'.$error['line'],$error['type'],1, $error['message']);

        } else { // 记录异常
            $trace_info     = $e->getTrace();
            logging($trace_info[0]['file'].'-line:'.$trace_info[0]['line'],'exception',1,$trace_info[0]['args']);

        }
        //可以在此交由系统处理
        return parent::render($e);


    }

}