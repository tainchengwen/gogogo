<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class MpNameRepository extends BaseRepository {
    /**
     * 添加馆区
     */
    public function addMpName($request){
      //查看这个 是否重复
      $info = DB::table('erp_mp_name')
                  -> where([
                    'flag'    => 0,
                    'mp_name' => $request->mp_name
                  ])
                  -> first();
      if($info){
          return [
              'code' => 500,
              'msg' => '名字重复'
          ];
      }
      if ($request->mp_flag === '是') {
          $mp_flag = 1;
      } else {
          $mp_flag = 0;
      }
      DB::table('erp_mp_name')
          -> insert([
            'mp_name'              => trim($request->mp_name),
            'image'                => 'ali_oss:'.$request->image,
            'icon_image'           => 'ali_oss:'.$request->imageSec,
            'freight_temp_name_id' => $request->freight_id,
            'created_at'           => time(),
            'updated_at'           => time(),
            'is_show'              => $mp_flag
          ]);
      return [
          'code' => 200,
          'msg' => '添加成功'
      ];
    }
    /**
     * 修改馆区
     */
    public function editMpname($request){

        $info = DB::table('erp_mp_name')
                    -> where('id', '!=', $request->id)
                    -> where('mp_name', $request->mp_name)
                    -> where('flag', 0)
                    -> first();
        if($info){
            return [
                'code' => 500,
                'msg' => '名字重复，请重新输入'
            ];
        } else {
            if ($request->mp_flag === '是') {
                $mp_flag = 1;
            } else {
                $mp_flag = 0;
            }
            $mpName = DB::table('erp_mp_name')
                          -> where('id', $request->id)
                          -> first();
            DB::table('erp_mp_name')
                -> where('id', $request->id)
                -> update([
                    'mp_name'              => $request->mp_name,
                    'is_show'              => $mp_flag,
                    'updated_at'           => time(),
                    'image'                => isset($request->image)?'ali_oss:'.$request->image:$mpName->image,
                    'icon_image'           => isset($request->imageSec)?'ali_oss:'.$request->imageSec:$mpName->icon_image,
                    'freight_temp_name_id' => isset($request->freight_id)?$request->freight_id:$mpName->freight_temp_name_id
                ]);
            return [
                'code' => 200,
                'msg' => '编辑成功'
            ];     
        }
    }


    public function mpNameInfo($request){
        $info = DB::table('erp_mp_name')
                    -> leftJoin('freight_temp_name','erp_mp_name.freight_temp_name_id','freight_temp_name.id')
                    -> where('erp_mp_name.id', $request->id)
                    -> select([
                        'erp_mp_name.freight_temp_name_id',
                        'freight_temp_name.temp_name',
                        'erp_mp_name.id',
                        'erp_mp_name.mp_name',
                        'erp_mp_name.is_show',
                        'erp_mp_name.image',
                        'erp_mp_name.icon_image'
                    ])
                    -> first();
        if($info->image){
            $info->image_url = getImageUrl($info->image);
            $info->image=substr($info->image,8);
        }
        if($info->icon_image){
            $info->icon_image_url = getImageUrl($info->icon_image);
            $info->icon_image=substr($info->icon_image,8);
        }
        if ($info->is_show === 1) {
            $info->mp_flag = "是";
        } else {
            $info->mp_flag = "否";
        }
        return [
          'code' => 200,
          'data' => $info        
        ];
    }

    /**
     * 馆区列表
     */
    public function mpNameList($request){
      $where = [];
      if($request->mp_name){
          $where[] = [
              'erp_mp_name.mp_name','like','%'.trim($request -> mp_name).'%'
          ];
      };
      $list = DB::table('erp_mp_name')
                  -> leftJoin('freight_temp_name','erp_mp_name.freight_temp_name_id','freight_temp_name.id')
                  -> where('erp_mp_name.flag', 0)
                  -> select([
                      'freight_temp_name.temp_name',
                      'erp_mp_name.id',
                      'erp_mp_name.mp_name',
                      'erp_mp_name.image',
                      'erp_mp_name.icon_image',
                      'erp_mp_name.flag',
                      'erp_mp_name.is_show',
                  ])
                  -> where($where)
                  -> paginate(isset($request->per_page)?$request->per_page:20);
      foreach ($list as $key => $value) {
          if ($value->is_show === 0) {
              $list[$key]->mpshow = '否';
          } else {
              $list[$key]->mpshow = '是';
          }
      }
      if($request -> return_type == 'option'){
          return Configure::dealCalssArray($list);
      }
      
      return $list;
    }

    /**
     * 删除馆区
     */
    public function deleteMpName($request){
        $info = DB::table('erp_mp_name')
                    -> where([
                        'id' => $request->id
                    ])
                    -> first();
        if ($info->flag === 1) {
            return [
                'code' => 500,
                'msg'  => '此馆区已被删除'
            ];
        }
        DB::table('erp_mp_name')
            -> where('id', $request->id)
            -> update([
                'updated_at' => time(),
                'flag'       => 1
            ]);
        return [
            'code' => 200,
            'msg' => '删除成功'
        ];
    }
}