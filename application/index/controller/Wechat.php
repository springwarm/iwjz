<?php
/**
 * 微信公众号控制器
 */
namespace app\index\controller;

use app\common\service\WechatService;
use think\Config;

class Wechat
{
    // 默认入口
    public function index()
    {
        $instance   = WechatService::getInstance(Config::get('wx_options'));
        $instance->replyInfo();

    }
}
