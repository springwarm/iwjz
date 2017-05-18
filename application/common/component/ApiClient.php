<?php
/**
 * Created by PhpStorm.
 * User: windfly
 * Date: 2017/4/18
 * Time: 22:11
 */
namespace app\common\component;

use think\Exception;

class ApiClient
{
	//请求的token
	const token='windfly';

	//请求url
	public $url = '';

	//请求的类型
	private $_requestType;

	//请求的数据
	private $data;

	//curl实例
	private $curl;

	public $status;

	private $headers = array();
	/**
	 * [__construct 构造方法, 初始化数据]
	 * @param [type] $url     请求的服务器地址
	 * @param [type] $requestType 发送请求的方法
	 */
	public function __construct($url = '',  $requestType = 'get')
	{
		if (!empty($url)) {
			$this->url      = $url;
		}
		$this->_requestType = $requestType;
		try{
			if(!$this->curl = curl_init()){
				throw new Exception('curl初始化错误：');
			};
		}catch (Exception $e){
			echo '<pre>';
			print_r($e->getMessage());
			echo '</pre>';
		}

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($this->curl, CURLOPT_HEADER, 1);
		return true;
	}

	/**
	 * [_post 设置get请求的参数]
	 * @return [type] [description]
	 */
	public function _get() {

	}

	/**
	 * [_post 设置post请求的参数]
	 * post 新增资源
	 * @return [type] [description]
	 */
	public function _post() {

		curl_setopt($this->curl, CURLOPT_POST, 1);

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);

	}

	/**
	 * [_put 设置put请求]
	 * put 更新资源
	 * @return [type] [description]
	 */
	public function _put() {

		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
	}

	/**
	 * [_delete 删除资源]
	 * delete 删除资源
	 * @return [type] [description]
	 */
	public function _delete() {
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

	}

	/**
	 * [doRequest 执行发送请求]
	 * @param [type] $url     请求的服务器地址
	 * @param [type] $data    发送的数据
	 * @param [type] $requestType 发送请求的方法
	 * @return [type] [description]
	 */
	public function doRequest($url  = '', array $data = array(),$requestType = '')
	{
		$requestType        = empty($requestType)? $this->_requestType: $requestType;
		$this->requestType  = strtolower($requestType);
		$numeric_prefix     = '';
		// PATHINFO模式
		if (!empty($data)) {
			$keys = array_keys($data);
			$ret  = $keys != array_keys($keys);
			if ($ret) {
				$numeric_prefix = 'var_';
			}
			$paramUrl   = http_build_query($data,$numeric_prefix);
			$url = $url .'?'. $paramUrl;
		}

		//初始化类中的数据
		$this->url = empty($url)? $this->url: $url;
		if ($this->url) {

			return false;
		}

		$this->data = $data;
		// 设置URL
		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		//发送给服务端验证信息
		if((null !== self::token) && self::token){
			$this->headers = array(
				'Client-Token:'.self::token,//此处不能用下划线
				'Client-Code:'.$this->setAuthorization()
			);
		}

		//发送头部信息
		$this->setHeader();

		//发送请求方式
		switch ($this->_requestType) {
			case 'post':
				$this->_post();
				break;

			case 'put':
				$this->_put();
				break;

			case 'delete':
				$this->_delete();
				break;

			default:
				curl_setopt($this->curl, CURLOPT_HTTPGET, TRUE);
				break;
		}
		//执行curl请求
		$info = curl_exec($this->curl);

		//获取curl执行状态信息
		$this->status = $this->getInfo();
		return $info;
	}

	/**
	 * 设置发送的头部信息
	 */
	private function setHeader(){
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
	}

	/**
	 * 生成授权码
	 * @return string 授权码
	 */
	private function setAuthorization(){
		$authorization = md5(substr(md5(self::token), 8, 24).self::token);
		return $authorization;
	}
	/**
	 * 获取curl中的状态信息
	 */
	public function getInfo(){
		return curl_getinfo($this->curl);
	}

	/**
	 * 关闭curl连接
	 */
	public function __destruct(){
		curl_close($this->curl);
	}
}