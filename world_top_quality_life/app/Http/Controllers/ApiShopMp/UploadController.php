<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\UploadRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class UploadController extends Controller
{
    public function __construct(UploadRepository $uploadRepository)
    {
        $this->uploadRepository = $uploadRepository;
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'file' => 'required|max:500'
            'file' => 'required'
        ]);
        if ($validator->fails()) {
            var_dump($validator->errors());exit;
            return $this->errorResponse($request, [], '请上传文件!');
        }

        $file = Input::file('file');

        if ($file->isValid()){

            $check = $this->uploadRepository->checkMimeType($file);
            if(!$check['success']){
                return $this->errorResponse($request, [], $check['msg']);
            }
    
            $uploadResult = $this->uploadRepository->uploadImage($file);

            if ($uploadResult['success']) {
                return $this->successResponse($request, $uploadResult['data']);
            }else{
                return $this->errorResponse($request, [], $uploadResult['msg']);
            }

        } else {
            return $this->errorResponse($request, [], '无效的文件!');
        }

    }

}