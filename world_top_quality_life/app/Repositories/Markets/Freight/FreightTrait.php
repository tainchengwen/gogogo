<?php

namespace App\Repositories\Markets\Freight;

use App\MarketFreight;

trait FreightTrait
{
    public function add($request)
    {
        $data=[
            'name'=>$request->name,
            'content'=>$request->content,
            'type'=>$request->type,
            'policy'=>$this->policyJson($request),
            'vip'=>implode(',',$request->audience),
            'created_at'=>time(),
            'begin_at'=>strtotime($request->begin_at),
            'end_at'=>strtotime($request->end_at),
        ];

        $freight_model=MarketFreight::create($data);

        !empty($request->selectmp) && $freight_model->mpnames()->attach($request->selectmp);

        !empty($request->selectcate) && $freight_model->cats()->attach($request->selectcate);

        !empty($request->selecttags) && $freight_model->tags()->attach($request->selecttags);

        !empty($request->selectskus) && $freight_model->skus()->attach($request->selectskus);

        !empty($request->selectunions) && $freight_model->unions()->attach($request->selectunions);

        return [
            'status'=>1,
            'msg'=>'添加成功'
        ];
    }
}
