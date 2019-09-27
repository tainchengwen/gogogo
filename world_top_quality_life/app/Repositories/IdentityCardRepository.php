<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class IdentityCardRepository extends BaseRepository
{
    /**
     * 新增地址
     */
    public function add($request)
    {
        // 如果当前为默认，则
        $data = [
            'userId'        => $request->user->wxUserId,
            'name'          => $request->name,
            'idNumber'      => $request->idNumber,
            'imageFront'    => $request->imageFront,
            'imageBack'     => $request->imageBack,
            'createdTime'   => time(),
            'updatedTime'   => time()
        ];

        $id = DB::table('erp_shopmp_identity_card') -> insertGetId($data);
        if (!empty($request->isDefault)) {
            $this->setDefault($request->user->wxUserId, $id);
        }

        return true;
    }

    public function get($ids)
    {
        $ids=is_array($ids)?$ids:[$ids];
        $idCard = DB::table('erp_shopmp_identity_card')
            -> where('isDel', 0)
            -> whereIn('id', $ids)
            -> select([
                'id',
                'userId',
                'name',
                'idNumber',
                'imageFront',
                'imageBack',
                'isDefault'
            ])
            -> first();

        return $idCard ? $this->assembleImage($idCard) : $idCard ;
    }

    public function checkExistAddressById($id, $userId)
    {
        if (empty($id)) {
            return false;
        }

        $count = DB::table('erp_shopmp_identity_card')
            -> where('userId', $userId)
            -> where('isDel', 0)
            -> where('id', $id)
            -> count();

        return $count > 0;
    }

    /**
     * 地址查询
     */
    //$request->type  1 需要身份证  2不需要
    public function search($request)
    {
        $list = DB::table('erp_shopmp_identity_card')
            -> where('userId', $request->user->wxUserId)
            -> where('isDel', 0)
            ->where(function($query) use ($request){
                switch($request->type){
                    case 1:
                        $query->where('imageFront','<>','')->where('imageBack','<>','');
                        break;
                    case 2:
                        break;
                    case 3:
                        break;
                }
            })
            -> select([
                "id",
                "userId",
                "name",
                "idNumber",
                "imageFront",
                "imageBack",
                "isDefault"
            ])
            -> get();
        foreach ($list as $key => $address) {
            $list[$key] = $this->assembleImage($address);
        }
        return $list;
    }


    private function checkStr2($str,$str2)
    {
        return preg_match($str2,$str) ? true : false;
    }

    /**
     * 编辑地址
     */
    public function edit($request, $id)
    {
        $idCard = $this->get($id);
        $pattern = '/http/';

        // 判断是否是全称
        // 如果是则只取最后一个名字
        if ($request->imageFront && $this->checkStr2($request->imageFront, $pattern)) {
            $a = explode('/', $request->imageFront);
            $request->imageFront = end($a);
        }

        if ($request->imageBack  && $this->checkStr2($request->imageBack, $pattern)) {
            $a = explode('/', $request->imageBack);
            $request->imageBack = end($a);
        }

        DB::table('erp_shopmp_identity_card')
            -> where('id', $request->id)
            -> update([
                'name'          => $request->name ? $request->name : $address->name ,
                'idNumber'      => $request->idNumber ? $request->idNumber : $idCard->idNumber ,
                'imageFront'    => $request->imageFront ? $request->imageFront : $idCard->imageFront ,
                'imageBack'     => $request->imageBack ? $request->imageBack : $idCard->imageBack ,
                'updatedTime'   => time()
            ]);

        if (!empty($request->isDefault)) {
            $this->setDefault($request->user->wxUserId, $id);
        }

        return true;
    }

    private function assembleImage($idCard)
    {
        $idCard->backName = $idCard->imageBack ? substr($idCard->imageBack,8):'';
        $idCard->frontName = $idCard->imageFront ?substr($idCard->imageFront,8):'';
        $idCard->imageFront = $idCard->imageFront ? getImageUrl($idCard->imageFront) : '' ;
        $idCard->imageBack = $idCard->imageBack ? getImageUrl($idCard->imageBack) : '' ;
        return $idCard;
    }

    /**
     * 获取默认地址
     */
    public function getDefault($userId)
    {
        $idCard = DB::table('erp_shopmp_identity_card')
            -> where('userId', $userId)
            -> where('isDel', 0)
            -> where('isDefault', 1)
            -> select([
                "id",
                "name",
                "userId",
                "idNumber",
                "imageFront",
                "imageBack",
            ])
            -> first();

        if ($idCard) {
            $idCard = $this->assembleImage($idCard);
            return $idCard;
        } else {
            return new \stdClass();
        }
    }

    /**
     * 删除地址
     */
    public function del($id)
    {
        DB::table('erp_shopmp_identity_card')
            ->where('id', $id)
            -> update([
                'isDel'         => 1,
                'updatedTime'   => time()
            ]);
        return true;
    }

    /**
     * 设为默认
     */
    public function setDefault($userId, $id)
    {
        // 设置这个为默认，
        DB::table('erp_shopmp_identity_card')
            -> where('id', $id)
            -> update([
                'isDefault'     => 1,
                'updatedTime'   => time()
            ]);

        // 其他所有都取消默认
        DB::table('erp_shopmp_identity_card')
            -> where('userId', $userId)
            -> where('id', '!=', $id)
            -> update([
                'isDefault'     => 0,
                'updatedTime'   => time()
            ]);

        return true;
    }
}
