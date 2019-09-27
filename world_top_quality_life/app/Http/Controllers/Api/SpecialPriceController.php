<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Repositories\SpecialRepository;
use App\Special;

class SpecialPriceController extends Controller
{
    public function __construct(SpecialRepository $specialRepository)
    {
        $this->special=$specialRepository;
    }

    //
    public function addList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d,'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list=$this->special->getAddList($request);

        return $list;
    }

    public function addUnionList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d,'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list=$this->special->getAddList($request,1);

        return $list;
    }

    public function index(Request $request)
    {
        $validator = Validator::make(['date'=>$request->date], [
            'date' => 'required|date_format:Y-m-d,'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $list=$this->special->indexSpecial($request);

        return $list;
    }

    public function store(Request $request)
    {
        foreach($request->data as $v){
            if($v['price'] <= 0 || $v['num'] <=0){
                return new JsonResponse('数据错误');
            }
        }

        $result=$this->special->storeSpecial($request);

        return $result;
    }

    public function storeUnion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'data' => 'required|array'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        foreach ($request->data as $v){
            foreach ($v as $k1=>$v1){
                if ($k1==0){
                    if (!is_numeric($v1['num']) || $v1['num']<1 || !is_numeric($v1['sort_index']) || $v1['sort_index']<0){
                        return [
                            'status'=>0,
                            'msg'=>'数据格式错误'
                        ];
                    }
                }else{
                    if (!is_numeric($v1['price']) || $v1['price']<=0){
                        return [
                            'status'=>0,
                            'msg'=>'数据格式错误'
                        ];
                    }
                }
            }
        }

        $result=$this->special->storeSpecialUnion($request);

        return $result;
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $result=$this->special->destroySpecial($request);

        return $result;
    }

    public function specialByLinkId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required'
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        $result=$this->special->getSpecialByLinkId($request->ids);

        return $result;
    }

    public function freightLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'special_ids' => 'required|array',
            'freight_ids' => 'sometimes|array',
        ]);
        if ($validator->fails()) {
            return new JsonResponse($validator->errors());
        }

        foreach ($request->special_ids as $special_id){
            $special=Special::find($special_id);
            $special->freights()->sync($request->freight_ids);
        }

        return ['status'=>1,'msg'=>'关联成功'];
    }
}
