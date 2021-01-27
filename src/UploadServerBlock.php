<?php
/**
 * Created by PhpStorm
 * Author: K_ui < louyuexing@outlook.com >
 * Time: 2020/03/31 20:46
 */

namespace kuiupload;

error_reporting(0);
date_default_timezone_set('PRC');

define('SALT',"salt_@_K_ui");

/**
 * Class UploadServerBlock
 */
class UploadServerBlock
{
    /**
	 * 文件保存路径
	 * @var string
	 */
	private $filepath='./upload';

	/**
	 * 保存文件名
	 * @var string
	 */
	private $filename='';

	/**
	 * 错误码
	 * @var array
	 */
	protected $code = [
		'US_1000' => '正常',
		'US_1001' => '参数未传入',
		'US_1002' => '参数不合法',
		'US_1003' => '参数被非法修改',
		'US_9999' => '操作失败',
	];

	/**
	 * 检查参数
	 * @var array
	 */
	private $checkkey=['data','check','hash','basename','step_No','count_step','size'];

	/**
	 * 文件数据
	 * @var string
	 */
	private $filedata='';

	/**
	 * 返回的数据
	 * @var array
	 */
	private $returndata=[];

	/**
	 * 最后返回数据
	 * @var string
	 */
	private $lastreturn='';

	/**
	 * 接收的数据
	 * @var array
	 */
	private $recvdata=[];

	private $fh;

	/**
	 * 初始化，注册代码结束处理
	 * UploadServer constructor.
	 */
	public function __construct()
	{
		register_shutdown_function([$this,'errorHandle']);
		$this->recvdata=$_POST;

		$this->defineErrno();
		$this->checkParams();
		$this->checkSign();
		$this->setFileName('',substr(sha1($this->recvdata['basename']),0,14));
	}

    /**
     * 设置文件名
     * @param string $prefix
     * @param string $suffix
     * @return bool
     */
	private function setFileName($prefix='',$suffix='')
    {
        $fileinfo=pathinfo($this->recvdata['basename']);
        $fname=$fileinfo['filename'];
        $prefix && $fname=$prefix.$fname;
        $suffix && $fname.=$suffix;
        $this->filename=$fname.'.'.$fileinfo['extension'];
        return true;
    }

    /**
     * 定义错误码
     * @return bool
     */
	private function defineErrno(){
        foreach ($this->code as $key=>$value) {
            defined($key) or define($key,$key);
        }
        return true;
    }

    /**
     * 参数检查
     * @return bool
     */
	private function checkParams()
	{
		foreach ($this->checkkey as $key=>$value) {
            !isset($this->recvdata[$value]) &&$this->returnResult(US_1001,$this->code[US_1001].'.'.$value);
		}
		return true;
	}

    /**
     * 签名校验
     * @return bool
     */
    private function checkSign()
    {
        $this->filedata=$this->recvdata['data'];
        $recheck=$this->recvdata['check'];
        $rechash=$this->recvdata['hash'];

        unset($this->recvdata['data']);
        unset($this->recvdata['check']);

        $this->returndata=$this->recvdata;

        ksort($this->recvdata);
        $sha1str=SALT;
        foreach ($this->recvdata as $k=>$sha1v) {
            $sha1str.=$sha1v;
        }
        $check=sha1($sha1str);
        $hash=sha1($this->filedata);

        if(($rscheck= ($check !=$recheck))  || ($rshash = ($hash != $rechash)))
            $this->returnResult(US_1003,$this->code[US_1003].' check='.$rscheck.' ;hash='.$rshash);
        return true;
    }

	/**
	 * 文件保存
	 * @param null $filepath
	 */
	public function save($filepath=null)
	{
		$savepath= $filepath ? $this->filepath=$filepath : $this->filepath;
		!is_dir($savepath) && mkdir($savepath,0777);
		$this->fh=fopen($savepath.'/'.$this->filename,'a');

		if($this->fh && is_resource($this->fh)) {
			$re = fwrite($this->fh, $this->filedata, $this->returndata['size']);
			if ($re) {
				$this->returndata['savefilename'] = $this->filename;
				$this->returnResult(US_1000, true, $this->returndata);
                return true;
			}
			$this->returnResult(US_9999, $this->code[US_9999] . 'fpc:' . $re);
		}
		$this->returnResult(US_9999,$this->code[US_9999].'fpc:');
        return false;
	}

	/**
	 * 结果输出
	 * @param $code
	 * @param bool $msg
	 * @param $data
	 */
	private function returnResult($code,$msg=true,$data=[])
	{
        is_resource($this->fh) && @fclose($this->fh);
		$std=new \stdClass();
		$std->code=$code;
		$std->msg=$msg;
		$std->data=['st'=>date('YmdHis',time()),'data'=>$data];
		$this->lastreturn=json_encode($std,true);
		exit;
	}

    /**
     * 异常处理
     */
	public function errorHandle()
	{
		$error=error_get_last();
		if($error || !empty($error)) {
			$std=new \stdClass();
			$std->code=US_9999;
			$std->msg=$error['message'];
			$std->data=['st'=>date('YmdHis',time()),'data'=>$error];
			$this->lastreturn=json_encode($std,true);
		}
		echo $this->lastreturn;
		exit;
	}
}

//(function($filepath=''){
//    (new UploadServer())->save($filepath);
//})('./upload');


