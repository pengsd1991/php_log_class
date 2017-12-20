<?php
//namespace hn_class;
/**
 * @todo hn统一公共日志生成类
 * 调用方法:通过单例接口获取类再写日志，$log = HnPublicLog::getinstance('folder');$log->hnLog($dataLog,$title,1);
 * 日志目录:shop/hnlog_/public_log/ ,按folder建立，可带斜杠建立多层目录
 * 日志级别说明:1正常日志级别 重要日志，程序运行就记录; 10 debug日志记录，自动添加session、post数据 调试代码找bug时记录; 100 页面输出日志、生成静态文本日志，本地环境使用,将debug级别日志输出页面
 * debug级别日志:当系统设置log级别为debug且该debug日志模块有开启才会记录日志
 * debug级别日志开关:shop/data/hn_define.php里的HN_LOG_LEVEL与HN_DEBUG_LOG_MODULE
 */
defined('HN_ROOT_PATH') or define('HN_ROOT_PATH', str_replace('hn_class/HnPublicLog.php', '', str_replace('\\', '/', __FILE__)));
define('HN_LOG_LEVEL',100);//1正常日志级别 重要日志，程序运行就记录;   10 debug日志记录，自动添加session、post数据 调试代码找bug时记录;   100 页面输出日志、生成静态文本日志，本地环境使用,将debug级别日志输出页面
defined('HN_DEBUG_LOG_MODULE') or define('HN_DEBUG_LOG_MODULE',"test,test_pengsd");//开启的debug日志模块，逗号分隔

class HnPublicLog
{
	static protected $instance; // 单例模式
	private $_folder;//日志目录

	const LOG_LEVEL_IMPORTANT = 1;//重要日志
	const LOG_LEVEL_DEBUG = 10;//debug日志
	const LOG_LEVEL_OUTPUT = 100;//页面输出日志

	/**
	 * @todo 返回可以定义日志的目录,需要时添加
	 */
	public function logFoldersConfig(){
		return array(
				'test',							//文件夹test
				'test/test_pengsd',				//文件夹test/test_pengsd
				'demo',							//文件夹demo         
			);
	}

	private function __construct(){
		
	}

	/**
	 * @todo 设置日志文件夹
	 * @param string $folder 日志文件夹可用斜杆分层建立文件夹
	 */
	public function setFolder($folder){
		$this->_folder = $folder;
	}

	/**
	 * @todo 声明一个getinstance()静态方法，用于检测是否有实例对象
	 * @param string $folder 日志文件夹可用斜杆分层建立文件夹
	 */
	public static function getinstance($folder = 'no_folder'){
		if(!self::$instance) self::$instance = new self();
		self::$instance->setFolder($folder);
		return self::$instance;
	}

	/**
	 * @todo   写入日志,路径shop/hnlog_/public_log/
	 * @param [array/string] $log  日志内容，数组字符串皆可
	 * @param string $title 日志标题
	 * @param int $return 1|0 是否返回日志内容
	 * @param int $logJson 0|1 传入json是否进行json编码
	 * @param int $logLevel 1正常日志级别 重要日志，程序运行就记录;   10 debug日志记录，自动添加session、post数据 调试代码找bug时记录;   100 页面输出日志、生成静态文本日志，本地环境使用,将debug级别日志输出页面
	 */
	public function hnLog($log,$title='',$logLevel=self::LOG_LEVEL_DEBUG,$return=0,$logJosn=0){
		//检查日志文件是否在可写日志配置中
		if(!in_array($this->_folder, $this->logFoldersConfig())) return;
		//达到define设置的日记级别才log
		if(HN_LOG_LEVEL >= $logLevel){
			if($logJosn) $log = json_encode($log, JSON_UNESCAPED_UNICODE);
			if($logLevel >= self::LOG_LEVEL_DEBUG){//日志级别debug以上输出session等信息
				$moduleArr = defined('HN_DEBUG_LOG_MODULE')?explode(',',HN_DEBUG_LOG_MODULE):false;
				if(!$moduleArr || !in_array($this->_folder, $moduleArr)) return ;//debug级别日志需判断该模块是否开启，未来开启则不记录
				$log = [
						'inputLog' 	=> 	$log,
						'SESSION'	=>	empty($_SESSION)?[]:$_SESSION,
						'POST'		=>  $_POST,
						'GET'		=>	$_GET,
						'INPUT'		=>	file_get_contents('php://input')
					];
			}
			$path = HN_ROOT_PATH.'hnlog_/public_log/'.$this->_folder;
			//建立日志文件夹
			$this->mkdirs($path);
			//按天建立日志文件
			$filename =  date('Y-m-d',time()).'.log';
			//拼凑请求url
			$data = date('Y-m-d H:i:s',time())." $title :"."\n";
			@$url  = 'http://'. $_SERVER['HTTP_HOST'].(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
		    $url  .= isset($_SERVER['QUERY_STRING']) ? '?'. $_SERVER['QUERY_STRING'] : '';
		    $data .= "请求链接:".$url."\n";
			$data .= var_export($log,true);
			$data .= "\n \n";
			//写入日志
			$res = file_put_contents($path.'/'.$filename, $data, FILE_APPEND);
			//deebug级别以上的日志输出页面页面输出
			if(HN_LOG_LEVEL == self::LOG_LEVEL_OUTPUT && ($logLevel == self::LOG_LEVEL_DEBUG || $logLevel == self::LOG_LEVEL_OUTPUT)) $this->pre($data);
			//执行方法返回内容
			if($return) return $data;
		}
	}

	/**
	 * @todo 创建目录
	 */
	private function mkdirs($dir, $mode = 0775){
		if (is_dir($dir) || @mkdir($dir, $mode)){
			return true;
		}
		if (!$this->mkdirs(dirname($dir), $mode)){
			return false;
		}
		return @mkdir($dir, $mode);
	}

	/**
	 * @todo 页面输出
	 */
	private function pre($string, $tabwidth = 3){
		$tab = str_repeat('&nbsp;', $tabwidth);
		$out = '<pre>' . $string . '</pre>' . "\n" . '';
		print($out);
	}
}