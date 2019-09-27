<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class UploadRepository extends BaseRepository
{
    //上传图片
    public function uploadImage($file)
    {
        $extension = $file->getClientOriginalExtension();
        \Illuminate\Support\Facades\Log::info(json_encode($_FILES));
        $savePath = public_path().'/uploads/images';
        $filename = md5(time().rand(10000,99999)).'.'.$extension;

        if(!$file->move($savePath,$filename)){
            return [
                'success' => false,
                'msg'     => '上传失败'
            ];
        } else {
            return [
                'success'   => true,
                'msg'       => '上传成功',
                'data'      => [
                    'fileName' => $filename,
                    'url'       => getImageUrl($filename)
                ]
            ];
        }
    }

    public function checkMimeType($file)
    {
        //文件类型
        $fileTypes = array('image/jpeg','image/png');
        if(in_array($file->getMimeType(),$fileTypes)) {
            return [
                'success' => true,
                'msg'     => '文件格式合法'
            ];
        }
        else {
            return [
                'success' => false,
                'msg'     => '文件格式不合法'
            ];
        }
    }
}