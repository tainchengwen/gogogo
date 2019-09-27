<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\SpecialRepository;
use App\Repositories\SPURepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Support\Facades\Validator;
use DB;

class SpecialPriceController extends Controller
{
    public function __construct(
        SPURepository $spuRepository,
        SpecialRepository $specialRepository
    ){
        $this->spuRepository = $spuRepository;
        $this->special=$specialRepository;
    }

    //
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $today=Carbon::today()->toDateString();
        $tomorrow=Carbon::tomorrow()->toDateString();

        $today_data=$this->special->getSpecialByDate($today);
        $tomorrow_data=$this->special->getSpecialByDate($tomorrow);

        $end_time=config('special.end_time');
        $today_end_unix_time=strtotime($today.' '.$end_time);

        $start_time=config('special.start_time');
        $tomorrow_start_unix_time=strtotime($tomorrow.' '.$start_time);
        $today_start_unix_time=strtotime($today.' '.$start_time);

        //在特价时间内才可以购买
        if (time() >= $today_start_unix_time && time() <= $today_end_unix_time){
            $buy_status=1;
        }else{
            $buy_status=0;
        }

        $data=[
            'today'=>[
                'date'=>$today,
                'buy_status'=>$buy_status,
                'start_unix_time'=>$today_start_unix_time,
                'end_unix_time'=>$today_end_unix_time,
                'list'=>$today_data
            ],
            'tomorrow'=>[
                'date'=>$tomorrow,
                'start_unix_time'=>$tomorrow_start_unix_time,
                'list'=>$tomorrow_data
            ]
        ];
        return $this->successResponse($request, $data);
    }

    /**
     * 限时特价商品详情
     */
    public function specialSpu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        $special_ids=explode('_',$request->id);
        $special=DB::table('erp_special_price')->whereIn('id',$special_ids)->first();

        // 校验商品是否存在，
        $spu = $this->spuRepository->getSPUById($special->mp_spu_link_id);

        if (empty($spu)) {
            return $this->errorResponse($request, [], '该商品已失效');
        }

        $param=[
            'user_id'=>$request->user->wxUserId,
            'spu_id'=>$spu->spu_id,
            'market_class'=>$request->user->market_class,
        ];
        if ($spu->spu_type==1){
            $sku=$this->special->getUnionSpecialSku($special_ids,$param);
        }else{
            $sku=$this->special->getSpecialSku($special_ids,$param);
        }

        $data=[
            'spu'   => $spu,
            'sku'   => $sku,
        ];

        return $this->successResponse($request, $data);
    }
}
