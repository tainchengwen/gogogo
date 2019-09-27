<?php

namespace App\Repositories;

use OSS\OssClient;
use OSS\Core\OssException;

class OssRepository extends BaseRepository
{
    protected $ossClient;
    protected $bucket;  //存储空间名称
    protected $accessKeyId;
    protected $accessKeySecret;
    protected $endpoint;

    public function __construct()
    {
        $this->accessKeyId=config('oss.accessKeyId');
        $this->accessKeySecret=config('oss.accessKeySecret');
        $this->endpoint=config('oss.endpoint');
        $this->bucket=config('oss.bucket');
    }

    public function getOssClient($location = 'sh')
    {
        $this->endpoint = $location == 'sh'? $this->endpoint :config('oss.endpoint'.$location);
        $this->bucket = $location == 'sh'? $this->bucket :config('oss.bucket'.$location);
        $this->ossClient = new OssClient(
            $this->accessKeyId,
            $this->accessKeySecret,
            $this->endpoint
        );

    }

    /**
     *后端上传文件至oss
     * $localfile 上传文件对象||地址字符串，如$request->file('img')
     * $ossDir 上传至oss该文件夹下
     * @return $object 文件地址
     */
    public function uploadFile($localfile,$location='sh',$ossDir='upload/')
    {
        if(is_string($localfile)){
            //本地文件位置
            $filePath=$localfile;
            $ext=strrchr($localfile,'.');
        }else{
            $filePath=$localfile->path();
            $ext='.'.$localfile->extension();
        }

        //oss文件名
        $ossDir=ends_with($ossDir,'/')?$ossDir:$ossDir.'/';
        $object=$ossDir.time().'_'.str_random(5).$ext;

        try{
            $this->getOssClient($location);
            $this->ossClient->uploadFile($this->bucket, $object, $filePath);
        } catch(OssException $e) {
            \Log::info($e->getMessage());
            return;
        }
        //兼容本地文件，加上头部ali_oss:区分
        if($location == 'sh')$object='ali_oss:'.$object;
        return $object;
    }

    /**
     * 字符串上传
     * @param $content
     * @param string $ossDir
     * @param string $ext
     * @return string|void
     */
    public function uploadStream($content, $ossDir='upload/', $ext='png')
    {
        $ossDir=ends_with($ossDir,'/')?$ossDir:$ossDir.'/';
        $object=$ossDir.time().'_'.str_random(5).'.'.$ext;

        try{
            $this->getOssClient();
            $this->ossClient->putObject($this->bucket, $object, $content);
        } catch(OssException $e) {
            \Log::info($e->getMessage());
            return;
        }

        //兼容本地文件，加上头部ali_oss:区分
        $object='ali_oss:'.$object;
        return $object;
    }

    /**
     *返回前端直传签名
     */
    public function getOssUploadSign($bucket,$prefix)
    {
        if(!empty($bucket)){
            $this->bucket=$bucket;
        }
        if(empty($prefix)){
            $prefix='mp_upload/';
        }
        $id= $this->accessKeyId;          // 请填写您的AccessKeyId。
        $key= $this->accessKeySecret;     // 请填写您的AccessKeySecret。
        // $host的格式为 bucketname.endpoint，请替换为您的真实信息。
        $host = 'https://'.$this->bucket.'.'.substr($this->endpoint,7);
        $dir = $prefix;          // 用户上传文件时指定的前缀。

        $now = time();
        $expire = 30;  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);


        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。
        return $response;
    }

    public function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
}
