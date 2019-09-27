<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\OssRepository;
use App\Http\Controllers\ApiShopMpAgent\Controller as BaseController;

class OssController extends BaseController
{
    //
    public function getSign(OssRepository $ossClient,Request $request)
    {
        $bucket=$dir='';
        if($request->has('bucket') && !empty($request->input('bucket'))){
            $bucket=$request->input('bucket');
        }
        if($request->has('dir') && !empty($request->input('dir'))){
            $dir=$request->input('dir');
        }

        $data=$ossClient->getOssUploadSign($bucket,$dir);

        return $this->successResponse($request,$data);
    }
}
