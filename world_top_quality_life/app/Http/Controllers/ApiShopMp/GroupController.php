<?php

namespace App\Http\Controllers\ApiShopMp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\MarketRepository;
use App\Repositories\SPURepository;
use DB;

class GroupController extends Controller
{
    public function __construct(MarketRepository $marketRepository,SPURepository $spuRepository)
    {
        $this->market=$marketRepository;
        $this->spuRepository = $spuRepository;
    }

    /**
     * 团购列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $spus=$this->market->getNowGroups(5);

        return $this->successResponse($request, $spus);
    }

    /**
     * 我的团购列表
     * @param Request $request
     * @return array
     */
    public function myGroups(Request $request)
    {
        $my_groups=DB::table('erp_market_open_groups')
            ->leftJoin('erp_market_group_buyers','erp_market_open_groups.id','erp_market_group_buyers.open_id')
            ->leftJoin('erp_market_groups','erp_market_open_groups.group_id','erp_market_groups.id')
            ->leftJoin('erp_mp_name_spu_link','erp_market_groups.mp_spu_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            ->select([
                'erp_market_group_buyers.group_detail_id',
                'erp_market_groups.id as group_id',
                'erp_market_groups.spu_type',
                'erp_market_groups.mp_spu_id',
                'erp_market_groups.group_people_num',
                'erp_market_groups.union_origin_price',
                'erp_market_groups.union_group_price',
                'erp_spu_list.name',
                'erp_spu_list.img',
                'erp_spu_list.sub_name',
                'erp_spu_list.type',
                'erp_mp_name.mp_name',
                'erp_market_open_groups.id as open_group_id',
            ])
            ->where('erp_market_group_buyers.user_id',$request->user->wxUserId)
            ->where('erp_market_group_buyers.order_status',1)
            ->orderBy('erp_market_open_groups.id','desc')
            ->paginate(5);

        foreach ($my_groups as $group){
            $first_sku=DB::table('erp_market_group_details')
                ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                ->where('erp_market_group_details.id',$group->group_detail_id)
                ->select([
                    'erp_market_group_details.price_id',
                    'erp_market_group_details.origin_price',
                    'erp_market_group_details.group_price',
                    'erp_product_list.image',
                ])
                ->first();
            if (empty($group->img)){
                if ($group->spu_type==1){
                    $union_detail_img=DB::table('erp_market_group_details')
                        ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                        ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                        ->where('erp_market_group_details.group_id',$group->group_id)
                        ->value('erp_product_list.image');
                    $group->image=getImageUrl($union_detail_img);
                }else{
                    $group->image=getImageUrl($first_sku->image);
                }
            }else {
                $group->image = getImageUrl($group->img);
            }
            //套餐就不用取了,组装一个
            if ($group->type==1){
                $first_sku=(object)['price_id'=>0,'origin_price'=>$group->union_origin_price,'group_price'=>$group->union_group_price];
            }
            $group->sku=$first_sku;
            //差价，立省多少钱
            $group->sku->diff=$group->sku->origin_price - $group->sku->group_price;
        }

        return $this->successResponse($request, $my_groups);
    }

    public function show(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'business_id'   => 'required|numeric|exists:erp_business,id'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        //根据group id获取mp spu link id
        $group=DB::table('erp_market_groups')->find($id);
        if ($group->flag==1 || $group->begin_at > time() || $group->end_at < time()){
            return $this->errorResponse($request, [], '该团购已失效');
        }

        // 组装数据  所有的skus
        $assembledSPU = $this->spuRepository->getAssembledSPUByRequestAndId($request, $group->mp_spu_id);

        if ($group->spu_type==0){
            //移除没有参加团购的sku
            $price_ids=DB::table('erp_market_group_details')->where('group_id',$id)->pluck('price_id');
            $assembledSPU['skus']=$assembledSPU['skus']->filter(function ($value, $key) use($price_ids){
                return $price_ids->contains($value->id);
            });
            $assembledSPU['skus']=$assembledSPU['skus']->each(function ($value, $key) use($id){
                $group_detail=DB::table('erp_market_group_details')->where('group_id',$id)->where('price_id',$value->id)->first();
                $value->origin_price=$group_detail->origin_price;//原价购买
                $value->group_price=$group_detail->group_price;//开团价
                //移除其他营销活动
                unset($value->market);
            });
        }else{
            foreach ($assembledSPU['skus'] as $sku){
                $sku->origin_price=$group->union_origin_price;
                $sku->group_price=$group->union_group_price;
                //移除其他营销活动
                unset($sku->market);
            }
        }

        //组装当前的参团列表
        //先更新每个开的团状态
        $open_groups=DB::table('erp_market_open_groups')->where('group_id',$id)->where('status',0)->get();
        DB::beginTransaction();
        try{
            $open_groups=$open_groups->filter(function ($value, $key) use($group){
                $user_count=DB::table('erp_market_group_buyers')->where('open_id',$value->id)->count();//已成团人数
                $first_user=DB::table('erp_market_group_buyers')->where('open_id',$value->id)->first();
                if ($user_count==1 && $first_user->order_status==0){
                    return false;
                }
                if (time() > $value->invalid_at){
                    //如果设置了机器人拼团
                    if ($group->support_bot){
                        $order_ids=DB::table('erp_market_group_buyers')->where('open_id',$value->id)->pluck('order_id');
                        $bot_count=$group->group_people_num - $user_count;//剩余拼团人数
                        $insert_data=[];
                        for ($i=0;$i<$bot_count;$i++){
                            $random_bot_user = DB::table('wxuser_bot')->inRandomOrder()->first();//随机取一个机器人用户
                            $insert_data[]=[
                                'open_id'=>$value->id,
                                'user_id'=>$random_bot_user->id,
                                'group_detail_id'=>0,
                                'created_at'=>$value->invalid_at,
                                'is_bot'=>1,
                                'order_status'=>1
                            ];
                        }
                        DB::table('erp_market_group_buyers')->insert($insert_data);
                        DB::table('erp_market_open_groups')->where('id',$value->id)->update(['status'=>1]);
                        DB::table('erp_stock_order')->whereIn('id',$order_ids)->update(['group_status'=>4]);
                    }else{
                        DB::table('erp_market_open_groups')->where('id',$value->id)->update(['status'=>2]);
                    }
                    return false;
                }
                return true;
            });
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
        }

        $open_groups=$open_groups->each(function ($value, $key) use($group){
            $open_people_count=DB::table('erp_market_group_buyers')->where('open_id',$value->id)->count();
            $value->diff_users=$group->group_people_num - $open_people_count;//还差几人成团
            //拼接开团人昵称头像
            $value->first_open_user=DB::table('erp_market_group_buyers')
                ->leftJoin('wxuser','erp_market_group_buyers.user_id','wxuser.id')
                ->where('open_id',$value->id)
                ->orderBy('erp_market_group_buyers.id','asc')
                ->select(['wxuser.nickname','wxuser.headimg'])->first();
        });
        $assembledSPU['spu']->open_groups=$open_groups;
        $assembledSPU['skus']=is_array($assembledSPU['skus'])?array_values($assembledSPU['skus']):array_values($assembledSPU['skus']->toArray());
        return $this->successResponse($request, $assembledSPU);
    }

    public function openGroup(Request $request,$id)
    {
        $info=DB::table('erp_market_open_groups')
            ->leftJoin('erp_market_groups','erp_market_open_groups.group_id','erp_market_groups.id')
            ->leftJoin('erp_mp_name_spu_link','erp_market_groups.mp_spu_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            ->where('erp_market_open_groups.id',$id)
            ->select([
                'erp_mp_name.mp_name',
                'erp_spu_list.name',
                'erp_spu_list.sub_name',
                'erp_spu_list.img',
                'erp_market_groups.id',
                'erp_market_groups.spu_type',
                'erp_market_groups.group_people_num',
                'erp_market_groups.union_origin_price',
                'erp_market_groups.union_group_price',
                'erp_market_open_groups.invalid_at',
            ])
            ->first();

        $detail=DB::table('erp_market_group_buyers')
            ->leftJoin('erp_market_group_details','erp_market_group_buyers.group_detail_id','erp_market_group_details.id')
            ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->where('erp_market_group_buyers.open_id',$id)
            ->where('erp_market_group_buyers.user_id',$request->user->wxUserId)
            ->select([
                'erp_market_group_details.origin_price',
                'erp_market_group_details.group_price',
                'erp_product_list.image',
            ])
            ->first();



        if ($info->spu_type==1){
            $info->origin_price=$info->union_origin_price;
            $info->group_price=$info->union_group_price;
        }else{
            $info->origin_price=$detail->origin_price;
            $info->group_price=$detail->group_price;
        }

        if (empty($info->img)){
            if ($info->spu_type==1){
                $union_detail_img=DB::table('erp_market_group_details')
                    ->leftJoin('erp_product_price','erp_market_group_details.price_id','erp_product_price.id')
                    ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
                    ->where('erp_market_group_details.group_id',$info->id)
                    ->value('erp_product_list.image');
                $info->image=getImageUrl($union_detail_img);
            }else{
                $info->image=getImageUrl($detail->image);
            }
        }else {
            $info->image=getImageUrl($info->img);
        }

        $buyers=DB::table('erp_market_group_buyers')
            ->leftJoin('wxuser',function($q){
                $q->on('erp_market_group_buyers.user_id','wxuser.id')
                    ->where('erp_market_group_buyers.is_bot',0);
            })
            ->leftJoin('wxuser_bot',function($q){
                $q->on('erp_market_group_buyers.user_id','wxuser_bot.id')
                    ->where('erp_market_group_buyers.is_bot',1);
            })
            ->where('erp_market_group_buyers.order_status',1)
            ->where('erp_market_group_buyers.open_id',$id)
            ->select([
                'erp_market_group_buyers.is_bot',
                'wxuser.headimg',
                'wxuser_bot.head_img',
            ])
            ->orderBy('erp_market_group_buyers.id','asc')
            ->get();

        $headimg=[];
        foreach ($buyers as $buyer){
            if ($buyer->is_bot==1){
                $headimg[]=$buyer->head_img;
            }else{
                $headimg[]=$buyer->headimg;
            }
        }
        $info->headimg=$headimg;
        $info->diff_user=$info->group_people_num - $buyers->count();
        if ($info->diff_user==0){
            $info->status_code='2';//跳订单列表
            $info->status_str='恭喜您拼单成功';
        }elseif($buyers->count()==1){
            $info->status_code='0';//邀请好友
            $info->status_str='恭喜您已开团成功';
        }else{
            $info->status_code='1';
            $info->status_str=$buyers->count().'人团 团购中';
        }

        return $this->successResponse($request, $info);
    }

    public function inviteGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'business_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '请求参数不正确');
        }

        $need = $this->market->fetchInviteInfo($request);

        //将需图片base64编码
        //头像
        $head_img_base64 = base64_encode("{$need['head_img']}?x-oss-process=image/format,png");
        $head_img_safe_base64 = str_replace(array('+','/','='), array('-','_',''), $head_img_base64);
        //spu image
        $spu_img_base64 = base64_encode("{$need['image']}?x-oss-process=image/resize,l_550,limit_0,h_550,m_pad");
        $spu_img_safe_base64 = str_replace(array('+','/','='), array('-','_',''), $spu_img_base64);

        //获取小程序码
        /*$group_id=DB::table('erp_market_open_groups')->where('id',$request->id)->value('group_id');
        $res = $this->spuRepository->mpQRCode("id={$group_id}&openid={$request->id}",[
            'page'  => 'pages/spell/spell-details/spell-details',
            'width' => 160
        ]);
        $qrcode =  str_replace(array('+','/','='), array('-','_',''), base64_encode($res));*/

        // 水印文字 base64 编码
        $name=mb_strlen($need['name']) > 12? mb_substr($need['name'], 0, 11).'...':$need['name'];
        $sub_name=mb_strlen($need['sub_name']) > 12? mb_substr($need['sub_name'], 0, 11).'...':$need['sub_name'];

        $name_base64=str_replace(array('+','/','='), array('-','_',''), base64_encode("{$name}"));
        $sub_name_base64=empty($sub_name)?'':str_replace(array('+','/','='), array('-','_',''), base64_encode("{$sub_name}"));
        $mp_base64=str_replace(array('+','/','='), array('-','_',''), base64_encode("{$need['mp']}"));
        $origin_price_base64=str_replace(array('+','/','='), array('-','_',''), base64_encode("原价:￥{$need['origin_price']}"));
        $group_price_base64=str_replace(array('+','/','='), array('-','_',''), base64_encode("￥{$need['group_price']}"));
        $group_people_num_base64=str_replace(array('+','/','='), array('-','_',''), base64_encode("{$need['group_people_num']}人成团"));
        $diff_user_base64=str_replace(array('+','/','='), array('-','_',''), base64_encode("还差{$need['diff_user']}人"));

        // 拼接分享需要的图片的路径
        $waterImagePath =
            "https://fenithcdn.oss-cn-shanghai.aliyuncs.com/".
            "upload/group_back.png?x-oss-process=image/resize,m_fixed,w_750,h_1148,limit_0/".
            "watermark,image_{$head_img_safe_base64},g_nw,x_140,y_70/".
            "watermark,image_{$spu_img_safe_base64},g_center,voffset_75/".
            "watermark,image_{$head_img_safe_base64},g_se,x_85,y_195/".//qrcode
            "watermark,text_{$mp_base64},color_FF6600,g_sw,x_80,y_310,size_26/".
            "watermark,text_{$name_base64},g_sw,x_190,y_310,size_26/".
            "watermark,text_{$origin_price_base64},color_887E77,g_sw,x_275,y_130,size_30/".
            "watermark,text_{$group_people_num_base64},color_FF6600,g_sw,x_80,y_220,size_24/".
            "watermark,text_{$diff_user_base64},color_FF6600,g_sw,x_180,y_220,size_24/".
            "watermark,text_{$group_price_base64},color_FF6600,g_sw,x_80,y_130,size_48";

        if (!empty($sub_name_base64)){
            $waterImagePath.="/watermark,text_{$sub_name_base64},g_sw,x_80,y_270,size_26";
        }

        return [
            'errno' => 0,
            'status' => 1,
            'data' => [
                'image' => $waterImagePath
            ]
        ];
    }
}
