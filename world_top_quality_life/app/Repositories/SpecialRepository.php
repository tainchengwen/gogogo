<?php

namespace App\Repositories;

use DB;
use Carbon\Carbon;
use App\Repositories\SKURepository;
use App\Repositories\UnionRepository;
use App\Repositories\MarketRepository;
use App\Special;

class SpecialRepository extends BaseRepository
{
    public function __construct(
        SKURepository $SKURepository,
        UnionRepository $unionRepository,
        MarketRepository $marketRepository
    ){
        $this->SKURepository=$SKURepository;
        $this->union=$unionRepository;
        $this->market=$marketRepository;
    }

    /**
     * 获取添加限时特价的列表
     * @param $request
     * @return mixed
     */
    public function getAddList($request,$type=0)
    {
        //搜索条件
        $where = [];
        if($request -> mp_name_id){
            //馆区
            $where[] = [
                'erp_mp_name_spu_link.mp_name_id','=',$request -> mp_name_id
            ];
        }
        if($request -> product_no){
            //商品编号
            $where[] = [
                'erp_product_list.product_no','like','%'.trim($request -> product_no).'%'
            ];
        }
        if($request -> product_name){
            //商品名称
            $where[] = [
                'erp_product_list.product_name','like','%'.trim($request -> product_name).'%'
            ];
        }
        if($request -> spu_class_id){
            //spu类别
            $where[] = [
                'erp_spu_list.class_id','=',$request -> spu_class_id
            ];
        }
        if($request -> class_id){
            //商品类别
            $where[] = [
                'erp_product_list.class_id','=',$request -> class_id
            ];
        }
        if($request -> brand_id){
            //商品品牌
            $where[] = [
                'erp_product_list.brand_id','=',$request -> brand_id
            ];
        }
        if($request -> series_id){
            //商品系列
            $where[] = [
                'erp_product_list.series_id','=',$request -> series_id
            ];
        }
        //类型1：套餐，0：普通
        $where[]=['erp_spu_list.type','=',$type];
        if ($type==1){
            $where[]=['erp_spu_list.type','=',$type];
            //$where[]=['erp_mp_name_spu_link.union_flag','=',0];
            $where[]=['erp_product_price.union_status','=',1];
            $where[]=['erp_product_price.union_num','>',0];
        }


        //先取出已在当前选择日期内的，再进行排除
        $specials=DB::table('erp_special_price')
            ->leftJoin('erp_mp_name_spu_link','erp_special_price.mp_spu_link_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_product_price',function($q){
                $q->on('erp_mp_name_spu_link.mp_name_id','=','erp_product_price.mp_name_id')
                    ->on('erp_special_price.sku_id','=','erp_product_price.product_id');
            })
            ->where('erp_special_price.flag',0)
            ->whereDate('erp_special_price.date',$request->date)
            ->select(['erp_product_price.id'])->pluck('id');

        $list=DB::table('erp_product_price')
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
            ->leftJoin('erp_spu_list','erp_spu_sku_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_spu_category','erp_spu_list.class_id','erp_spu_category.id')//spu类别
            ->leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_spu_sku_link.spu_id', '=', 'erp_mp_name_spu_link.spu_id')
                    ->on('erp_product_price.mp_name_id', '=', 'erp_mp_name_spu_link.mp_name_id');
            })
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            ->where('erp_product_list.flag',0)
            ->where('erp_spu_sku_link.flag',0)
            ->where('erp_spu_list.flag',0)
            ->where('erp_mp_name_spu_link.flag',0)
            ->where('erp_mp_name.flag',0)
            ->where('erp_product_price.flag', 0)
            ->where('erp_product_price.status', 1)
            ->where('erp_product_price.is_show', 1)
            ->where('erp_product_price.has_stock', '>', 0)
            ->where($where)
            ->whereNotIn('erp_product_price.id',$specials)
            ->select([
                'erp_product_list.id as sku_id',//sku id
                'erp_product_list.product_no',//商品编号
                'erp_product_list.product_name',//商品名称
                'erp_spu_list.id as spu_id',//spu id
                'erp_spu_list.name as spu_name',//spu标题
                'product_class.name as product_class_name',//类别
                'product_brand.name as product_brand_name',//品牌
                'product_series.name as product_series_name',//系列
                'erp_mp_name_spu_link.id as mp_spu_link_id',//link id
                'erp_mp_name.id as mp_name_id',//馆区id
                'erp_mp_name.mp_name',//馆区名称
                'erp_product_price.price_a',
                'erp_product_price.price_b',
                'erp_product_price.price_c',
                'erp_product_price.price_d',
                'erp_product_price.price_s',
                'erp_product_price.has_stock',//库存
            ])
            ->orderBy('spu_id','desc')->paginate(isset($request -> per_page)?$request -> per_page:20);

        $result=$list->groupBy(['spu_id','spu_name']);

        //配合前端改数据树形格式
        $arr=[];

        foreach($result as $k=>$v){
            foreach($v as $k1=>$v1){
                $skus[]=['spu_id'=>$k,'spu_name'=>$k1];
                foreach($v1 as $k2=>$v2){
                    $v2->sort_id=$v2->mp_name_id.$v2->spu_id.$v2->sku_id;
                    $skus[]=$v2;
                    $skus[0]['mp_spu_link_id']=$v2->mp_spu_link_id;
                }
            }
            $arr['data'][]=$skus;
            $skus=[];
        }
        $arr['current_page']=$list->currentPage();
        $arr['per_page']=$list->perPage();
        $arr['total']=$list->total();

        return $arr;
    }

    /**
     * 指定日期的限时特价列表
     * @param $request
     * @return mixed
     */
    public function indexSpecial($request)
    {
        $list=DB::table('erp_special_price')
            ->leftJoin('erp_product_list','erp_special_price.sku_id','erp_product_list.id')
            ->leftJoin('erp_mp_name_spu_link','erp_special_price.mp_spu_link_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_spu_sku_link',function ($q){
                $q->on('erp_spu_list.id','erp_spu_sku_link.spu_id')
                    ->on('erp_special_price.sku_id','erp_spu_sku_link.sku_id');
            })
            ->leftJoin('erp_product_price', function($q) {
                $q->on('erp_special_price.sku_id', '=', 'erp_product_price.product_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'erp_product_price.mp_name_id');
            })
            ->where('erp_special_price.flag',0)
            ->where('erp_spu_sku_link.flag',0)
            ->whereDate('erp_special_price.date',$request->date)
            ->select([
                'erp_special_price.*',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_price.price_a',
                'erp_product_price.price_b',
                'erp_product_price.price_c',
                'erp_product_price.price_d',
                'erp_product_price.price_s',
                'erp_product_price.union_num',
                'erp_product_price.has_stock',//库存
                'erp_spu_list.id as spu_id',
                'erp_spu_list.name as spu_name',
                'erp_spu_list.type as spu_type',
                'erp_mp_name_spu_link.id as link_id',
                'erp_mp_name_spu_link.mp_name_id',
            ])
            ->orderBy('erp_special_price.sort_index','asc')
            ->get();

        //塞上运费优惠名字
        foreach ($list as $v){
            $special_model=Special::with('freights')->find($v->id);
            foreach($special_model->freights as $k=>$freight){
                $v->freight_names[]=$freight;
            }
        }

        $list=$list->groupBy('spu_type');

        $nomal_list=$list->has('0')?$list->get('0'):collect();
        $union_list=$list->has('1')?$list->get('1')->groupBy(['spu_id','spu_name']):collect();

        foreach ($nomal_list as $vo){
            $vo->sort_id=$vo->mp_name_id.$vo->spu_id.$vo->sku_id;//给前端去重用
        }

        $arr=[];

        foreach($union_list as $k=>$v){
            foreach($v as $k1=>$v1){
                $skus[]=['spu_id'=>$k,'spu_name'=>$k1];
                foreach($v1 as $k2=>$v2){
                    if($k2==0){
                        $skus[0]['sort_index']=$v2->sort_index;
                        $skus[0]['num']=$v2->num;
                        $skus[0]['sold_num']=$v2->sold_num;
                        $skus[0]['mp_spu_link_id']=$v2->link_id;
                        $skus[0]['sort_id']=$v2->link_id;
                    }
                    $skus[0]['ids'][]=$v2->id;
                    unset($v2->sort_index,$v2->spu_name,$v2->num,$v2->sold_num);
                    $skus[]=$v2;
                }
            }
            $arr[]=$skus;
            $skus=[];
        }

        if ($request->type==1){
            return ['union_list'=>$arr];
        }
        return ['nomal_list'=>$nomal_list,'union_list'=>$arr];
    }

    /**
     * 保存添加的限时特价
     * @param $request
     * @return array
     */
    public function storeSpecial($request)
    {
        try{
            $insertData=[];
            $date = $request->date;
            foreach ($request->data as $v){
                if(isset($v['id'])){
                    DB::table('erp_special_price')->where('id',$v['id'])
                        ->update(['num'=>$v['num'],'price'=>$v['price'],'sort_index'=>$v['sort_index']]);
                }else{
                    $insertData[]=[
                        'mp_spu_link_id' => $v['mp_spu_link_id'],
                        'sku_id'         => $v['sku_id'],
                        'num'            => $v['num'],
                        'price'          => $v['price'],
                        'sort_index'     => $v['sort_index'],
                        'date'           => $date,
                    ];
                }
            }
            $insertData=$this->array_unique_fb($insertData);
            if(!empty($insertData)){
                DB::table('erp_special_price')->insert($insertData);
            }
        }catch (\Exception $e){
            return [
                'status'=>0,
                'msg'=>'添加失败'
            ];
        }

        return [
            'status'=>1,
            'msg'=>'添加成功'
        ];
    }

    public function storeSpecialUnion($request)
    {
        try{
            $insertData=[];
            $date = $request->date;
            foreach ($request->data as $v){
                foreach ($v as $k1=>$v1){
                    if($k1==0)
                        continue;
                    if(isset($v1['id'])){
                        DB::table('erp_special_price')->where('id',$v1['id'])
                            ->update(['num'=>$v[0]['num'],'price'=>$v1['price'],'sort_index'=>$v[0]['sort_index']]);
                    }else{
                        $insertData[]=[
                            'mp_spu_link_id' => $v1['mp_spu_link_id'],
                            'sku_id'         => $v1['sku_id'],
                            'num'            => intval($v[0]['num']),
                            'price'          => $v1['price'],
                            'sort_index'     => intval($v[0]['sort_index']),
                            'date'           => $date,
                        ];
                    }
                }
            }
            if(!empty($insertData)){
                DB::table('erp_special_price')->insert($insertData);
            }
        }catch (\Exception $e){
            return [
                'status'=>0,
                'msg'=>'添加失败'
            ];
        }

        return [
            'status'=>1,
            'msg'=>'添加成功'
        ];
    }

    /**
     * 删除指定的限时特价
     * @param $request
     * @return array
     */
    public function destroySpecial($request)
    {
        try{
            DB::table('erp_special_price')
                ->whereIn('id',$request->ids)
                ->update([
                'flag'=>1,
            ]);
        }catch (\Exception $e){
            return [
                'status'=>0,
                'msg'=>'删除失败'
            ];
        }

        return [
            'status'=>1,
            'msg'=>'删除成功'
        ];
    }

    /**
     *获取小程序首页限时特价列表
     */
    public function getIndexSpecialList()
    {
        $today=Carbon::today()->toDateString();

        $list=$this->getSpecialByDate($today,6);

        $start_time=config('special.start_time');
        $end_time=config('special.end_time');
        $start_hour=explode(':',$start_time)[0];
        $end_unix_time=strtotime($today.' '.$end_time);

        $data=['start_hour'=>$start_hour,'list'=>$list];

        $today_start_unix=strtotime($today.' '.$start_time);
        $today_end_unix=strtotime($today.' '.$end_time);
        $now=time();

        if ($now >= $today_start_unix && $now <= $today_end_unix){
            $end_unix_time=$today_end_unix;
            $data['end_unix_time']=$end_unix_time;
        }elseif ($now < $today_start_unix){
            $data['start_unix_time']=$today_start_unix;
        }elseif ($now > $today_end_unix){
            $tomorrow=Carbon::tomorrow()->toDateString();
            $start_unix_time=strtotime($tomorrow.' '.$start_time);
            $data['start_unix_time']=$start_unix_time;
        }

        return $data;

    }

    /**
     * 根据日期获取特价列表
     * @param $date
     * @return mixed
     */
    public function getSpecialByDate($date,$num='')
    {
        $query=DB::table('erp_special_price')->leftJoin('erp_product_list','erp_special_price.sku_id','erp_product_list.id')
            ->leftJoin('erp_mp_name_spu_link','erp_special_price.mp_spu_link_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_spu_list','erp_mp_name_spu_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_spu_sku_link',function ($q){
                $q->on('erp_spu_list.id','erp_spu_sku_link.spu_id')
                    ->on('erp_special_price.sku_id','erp_spu_sku_link.sku_id');
            })
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            ->leftJoin('erp_product_price',function ($q){
                $q->on('erp_mp_name.id','erp_product_price.mp_name_id')
                    ->on('erp_special_price.sku_id','erp_product_price.product_id');
            })
            ->where('erp_special_price.flag',0)
            ->where('erp_spu_sku_link.flag',0)
            ->whereDate('date',$date)
            /*->where(function($query) {
                $query->where([
                    ['erp_spu_list.type', '=', 1],
                    ['erp_mp_name_spu_link.union_flag', '=', '0'],
                ])->orWhere('erp_spu_list.type', '=', '0');
            })*/
            ->select([
                'erp_product_list.product_name',
                'erp_product_list.image',
                'erp_special_price.id as special_id',
                'erp_special_price.price as special_price',
                'erp_special_price.mp_spu_link_id as link_id',
                'erp_special_price.sku_id as sku_id',
                'erp_special_price.num',
                'erp_special_price.sold_num',
                'erp_special_price.sort_index',
                'erp_mp_name.id as mp_name_id',
                'erp_mp_name.mp_name',
                'erp_spu_list.name as spu_name',
                'erp_spu_list.sub_name as spu_sub_name',
                'erp_spu_list.id as spu_id',
                'erp_spu_list.type as spu_type',
                'erp_product_price.union_num',
                'erp_product_price.price_d',
            ]);
        if($num){
            $query=$query->take($num);
        }
        $list=$query->orderBy('sort_index','asc')->orderBy('erp_product_list.id','desc')->get();

        foreach ($list as $v){
            if($v->image){
                $v->image=getImageUrl($v->image);
            }
            $v->percent=ceil($v->sold_num*100/$v->num);
            $v->percent=$v->percent > 100?100:$v->percent;
            unset($v->sold_num,$v->num);
        }

        //处理特价套餐
        $union_spu_ids=[];
        $new_list=collect();
        foreach ($list as $k=>$v){
            if ($v->spu_type==0){
                $new_list->push($v);
            }elseif ($v->spu_type==1){
                $result=$this->union->checkUnionStatus($v,'special');
                if (!$result){
                    unset($list[$k]);
                    continue;
                }
                if (in_array($v->spu_id,$union_spu_ids)){
                    $new_list->each(function ($item, $key) use($v) {
                        if($item->spu_id==$v->spu_id){
                            $item->special_id.='_'.$v->special_id;
                            $item->special_price+=$v->special_price*$v->union_num;
                            $item->price_d+=$v->price_d*$v->union_num;
                            $item->skuName[]=['name'=>$v->product_name,'num'=>$v->union_num];
                        }
                    });
                }else{
                    $v->special_price=$v->special_price*$v->union_num;
                    $v->price_d      =$v->price_d*$v->union_num;
                    $v->skuName[]=['name'=>$v->product_name,'num'=>$v->union_num];
                    $new_list->push($v);
                    $union_spu_ids[]=$v->spu_id;
                }
            }
        }

        //给前端用来做赚大钱功能
        foreach ($new_list as $v){
            $v->sku=(object)['originPrice'=>$v->price_d,'currentPrice'=>$v->special_price];
            $v->spu_image=$v->image;
            $v->id=$v->spu_id;
            $v->sub_name=$v->spu_sub_name;
            $v->name=$v->spu_name;
        }

        return $new_list;
    }

    public function getSpecialSku($ids,$param=[])
    {
        $sku=DB::table('erp_special_price')
            ->leftJoin('erp_mp_name_spu_link','erp_special_price.mp_spu_link_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_product_price', function($q) {
                $q->on('erp_special_price.sku_id', '=', 'erp_product_price.product_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'erp_product_price.mp_name_id');
            })
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->whereIn('erp_special_price.id',$ids)
            ->select([
                'erp_product_price.product_id as skuId',
                'erp_product_price.min_unit',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.image',
                'erp_product_list.class_id',
                'erp_product_price.has_stock',
                'erp_product_price.id as price_id',
                'erp_product_price.min_unit',
                'erp_product_price.mp_name_id',
                'erp_product_price.price_d as originPrice',
                'erp_special_price.id as special_id',
                'erp_special_price.num',
                'erp_special_price.price',
                'erp_special_price.price as currentPrice',
                'erp_special_price.sold_num',
                'erp_special_price.date',
                'erp_special_price.flag',
            ])
            ->first();

        $sku->headImage=getImageUrl($sku->image);
        //处理多图
        $sku->images=$this->SKURepository->assembledImages($sku);

        //检测库存
        if($sku->num > $sku->sold_num && $sku->has_stock > 0){
            $sku->can_buy_num=min($sku->has_stock ,($sku->num - $sku->sold_num));
        }else{
            $sku->can_buy_num=0;
        }

        //限购
        $sku->cannot_buy_reason='库存不足';
        if (!empty($param)){
            $param['sku_id']=$sku->skuId;
            $limit_buy_num=$this->market->checkLimitBuy($param);
            if ($limit_buy_num!=='unlimit' && ($sku->can_buy_num > $limit_buy_num)){
                $sku->can_buy_num = $limit_buy_num;
                $sku->cannot_buy_reason='已达到限购数量';
            }
        }

        //检测状态和时间
        if($sku->flag==0) {
            $start_time = config('special.start_time');
            $end_time = config('special.end_time');
            $special_start = Carbon::createFromFormat('Y-m-d H:i:s', $sku->date . ' ' . $start_time);
            $special_end = Carbon::createFromFormat('Y-m-d H:i:s', $sku->date . ' ' . $end_time);
            $now = Carbon::now();
            if ($now->gt($special_start) && $now->lt($special_end) && $sku->can_buy_num > 0) {
                $sku->special_code = 0;
                $sku->special_status = '正在特价中';
                $sku->end_unix_time = strtotime($special_end);
            } elseif ($now->lt($special_start)) {
                $sku->special_code = 1;
                $sku->special_status = '即将特价';
                $sku->start_unix_time = strtotime($special_start);
            } else {
                $sku->special_code = 2;
                $sku->special_status = '已失效的特价';
            }
        }else{
            $sku->special_code = 2;
            $sku->special_status = '已失效的特价';
        }

        //优惠活动
        $sku->market=[];
        //--添加正参与的运费活动
        $sku->market['freight'][]=['name'=>'限时特价包邮'];

        //移除不需要的字段
        unset($sku->image,$sku->num,$sku->sold_num,$sku->has_stock,$sku->date,$sku->flag);

        return $sku;
    }
    public function getUnionSpecialSku($ids,$param=[])
    {
        $skus=DB::table('erp_special_price')
            ->leftJoin('erp_mp_name_spu_link','erp_special_price.mp_spu_link_id','erp_mp_name_spu_link.id')
            ->leftJoin('erp_product_price', function($q) {
                $q->on('erp_special_price.sku_id', '=', 'erp_product_price.product_id')
                    ->on('erp_mp_name_spu_link.mp_name_id', '=', 'erp_product_price.mp_name_id');
            })
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->whereIn('erp_special_price.id',$ids)
            //->where('erp_mp_name_spu_link.union_flag',0)
            ->where('erp_product_price.union_status',1)
            ->where('erp_product_price.union_num','>',0)
            ->select([
                'erp_product_price.product_id as sku_id',
                'erp_product_list.product_no',
                'erp_product_list.product_name',
                'erp_product_list.image',
                'erp_product_list.class_id',
                'erp_product_price.has_stock',
                'erp_product_price.id as price_id',
                'erp_product_price.min_unit',
                'erp_product_price.union_num',
                'erp_product_price.mp_name_id',
                'erp_product_price.price_d',
                'erp_special_price.id as special_id',
                'erp_special_price.num',
                'erp_special_price.price',
                'erp_special_price.sold_num',
                'erp_special_price.date',
                'erp_special_price.flag',
            ])
            ->get();

        $union=new \stdClass();//拼接套餐信息
        if ($skus->isEmpty()){
            $union->invalid=true;
            $union->special_code = 2;
            $union->special_status = '已失效的特价';
        }else{
            $union->info=[];
            $union->images=[];
            $union->price_id=0;
            $union->min_unit=1;
            $union->can_buy_num=0;
            $union->price=0;
            $union->originPrice=0;
            $union->cartNum=0;
            $union->headImage='';
            $union->special_id='';
            $union->skuName=[];
            $union->union_num=[];
            $union->union_price=[];
            $union->productIds=[];
            $stock_status=0;
            $date='';
            foreach ($skus as $k=>$v){
                if ($v->has_stock < $v->union_num){
                    $stock_status=1;
                    //break;
                }
                if($k==0){
                    $union->can_buy_num=min(floor($v->has_stock / $v->union_num),($v->num-$v->sold_num));
                    $union->headImage=getImageUrl($v->image);
                    $union->price_id=$v->price_id;
                    $union->special_id=$v->special_id;
                    $date=$v->date;
                }else{
                    $union->can_buy_num=min($union->can_buy_num,floor($v->has_stock / $v->union_num));
                    $union->special_id.='_'.$v->special_id;
                }
                $union->info[]=['name'=>$v->product_name,'num'=>$v->union_num];
                $union->images[]=['url'=>getImageUrl($v->image)];
                $union->price += ($v->price*$v->union_num);
                $union->originPrice += ($v->price_d*$v->union_num);
                $union->union_num[$v->sku_id]=$v->union_num;
                $union->union_price[$v->sku_id]=$v->price;
                $union->productIds[]=$v->sku_id;
            }
            if ($stock_status == 1 || $union->can_buy_num < 1){
                $union->invalid=true;
                $union->special_code = 2;
                $union->special_status = '已失效的特价';
            }
            $union->price=$this->math_add($union->price,0);
            $union->currentPrice=$union->price;

            //限购
            $union->cannot_buy_reason='库存不足';
            if (!empty($param)){
                $limit_buy_num=$this->market->checkLimitBuy($param);
                if ($limit_buy_num!=='unlimit' && ($union->can_buy_num > $limit_buy_num)){
                    $union->can_buy_num = $limit_buy_num;
                    $union->cannot_buy_reason='已达到限购数量';
                }
            }

            //检测时间
            $start_time = config('special.start_time');
            $end_time = config('special.end_time');
            $special_start = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $start_time);
            $special_end = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $end_time);
            $now = Carbon::now();
            if ($now->gt($special_start) && $now->lt($special_end) && $union->can_buy_num > 0) {
                $union->special_code = 0;
                $union->special_status = '正在特价中';
                $union->end_unix_time = strtotime($special_end);
            } elseif ($now->lt($special_start)) {
                $union->special_code = 1;
                $union->special_status = '即将特价';
                $union->start_unix_time = strtotime($special_start);
            } else {
                $union->special_code = 2;
                $union->special_status = '已失效的特价';
            }
        }

        //优惠活动
        $union->market=[];
        //--添加正参与的运费活动
        $union->market['freight'][]=['name'=>'限时特价包邮'];

        return $union;
    }

    public function getSpecialByLinkId($ids)
    {
        $list=DB::table('erp_product_price')
            ->leftJoin('erp_product_list','erp_product_price.product_id','erp_product_list.id')
            ->leftJoin('erp_spu_sku_link','erp_product_list.id','erp_spu_sku_link.sku_id')
            ->leftJoin('erp_spu_list','erp_spu_sku_link.spu_id','erp_spu_list.id')
            ->leftJoin('erp_spu_category','erp_spu_list.class_id','erp_spu_category.id')//spu类别
            ->leftJoin('erp_mp_name_spu_link', function($q) {
                $q->on('erp_spu_sku_link.spu_id', '=', 'erp_mp_name_spu_link.spu_id')
                    ->on('erp_product_price.mp_name_id', '=', 'erp_mp_name_spu_link.mp_name_id');
            })
            ->leftJoin('erp_mp_name','erp_mp_name_spu_link.mp_name_id','erp_mp_name.id')
            //类别
            -> leftJoin('erp_product_class as product_class','erp_product_list.class_id','product_class.id')
            //品牌
            -> leftJoin('erp_product_class as product_brand','erp_product_list.brand_id','product_brand.id')
            //系列
            -> leftJoin('erp_product_class as product_series','erp_product_list.series_id','product_series.id')
            ->where('erp_product_list.flag',0)
            ->where('erp_spu_sku_link.flag',0)
            ->where('erp_spu_list.flag',0)
            ->where('erp_mp_name_spu_link.flag',0)
            ->where('erp_mp_name.flag',0)
            ->where('erp_product_price.flag', 0)
            ->where('erp_product_price.status', 1)
            ->where('erp_product_price.is_show', 1)
            ->where('erp_product_price.has_stock', '>', 0)
            ->where('erp_product_price.union_status', 1)
            ->whereIn('erp_mp_name_spu_link.id', $ids)
            ->select([
                'erp_product_list.id as sku_id',//sku id
                'erp_product_list.product_no',//商品编号
                'erp_product_list.product_name',//商品名称
                'erp_spu_list.id as spu_id',//spu id
                'erp_spu_list.name as spu_name',//spu标题
                'product_class.name as product_class_name',//类别
                'product_brand.name as product_brand_name',//品牌
                'product_series.name as product_series_name',//系列
                'erp_mp_name_spu_link.id as mp_spu_link_id',//link id
                'erp_mp_name.id as mp_name_id',//馆区id
                'erp_mp_name.mp_name',//馆区名称
                'erp_product_price.price_a',
                'erp_product_price.price_b',
                'erp_product_price.price_c',
                'erp_product_price.price_d',
                'erp_product_price.price_s',
                'erp_product_price.union_num',
                'erp_product_price.has_stock',//库存
            ])
            ->orderBy('spu_id','desc')->get();

        $result=$list->groupBy(['spu_id','spu_name']);

        $arr=[];

        foreach($result as $k=>$v){
            foreach($v as $k1=>$v1){
                $skus[]=['spu_id'=>$k,'spu_name'=>$k1];
                foreach($v1 as $k2=>$v2){
                    unset($v2->spu_name);
                    $skus[0]['mp_spu_link_id']=$v2->mp_spu_link_id;
                    $skus[]=$v2;
                }
            }
            $arr[]=$skus;
            $skus=[];
        }

        return $arr;
    }

    //去重
    private function array_unique_fb($array2D) {
        $temp=[];
        foreach ($array2D as $k=>$v) {
            $uniq_id = $v['mp_spu_link_id'].$v['sku_id'];

            if (in_array($uniq_id,$temp)){
                unset($array2D[$k]);
            }else{
                $temp[]=$uniq_id;
            }
        }
        return $array2D;
    }
}
