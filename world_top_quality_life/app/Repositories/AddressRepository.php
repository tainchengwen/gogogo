<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AddressRepository extends BaseRepository
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
            'phone'         => $request->phone,
            'province'      => $request->province,
            'city'          => $request->city,
            'area'          => $request->area,
            'detail'        => $request->detail,
            'idNumber'      => $request->idNumber ? $request->idNumber : '',
            'imageFront'    => $request->imageFront ? $request->imageFront : '',
            'imageBack'     => $request->imageBack ? $request->imageBack : '',
            'createdTime'   => time(),
            'updatedTime'   => time()
        ];

        $id = DB::table('erp_mp_shop_address') -> insertGetId($data);
        if (!empty($request->isDefault)) {
            $this->setDefault($request->user->wxUserId, $id);
        }

        return true;
    }

    public function addSend($request)
    {
        // 如果当前为默认，则
        $data = [
            'userId'        => $request->user->wxUserId,
            'name'          => $request->name,
            'phone'         => $request->phone,
            'province'      => $request->province,
            'city'          => $request->city,
            'area'          => $request->area,
            'detail'        => $request->detail,
            'idNumber'      => $request->idNumber ? $request->idNumber : '',
            'imageFront'    => $request->imageFront ? $request->imageFront : '',
            'imageBack'     => $request->imageBack ? $request->imageBack : '',
            'createdTime'   => time(),
            'updatedTime'   => time()
        ];

        $id = DB::table('erp_mp_shop_send_address') -> insertGetId($data);
        if (!empty($request->isDefault)) {
            $this->setSendDefault($request->user->wxUserId, $id);
        }

        return true;
    }

    public function get($id)
    {
        $address = DB::table('erp_mp_shop_address')
        -> where('isDel', 0)
        -> where('id', $id)
        -> select([
            'id',
            'userId',
            'name',
            'phone',
            'province',
            'city',
            'area',
            'detail',
            'idNumber',
            'imageFront',
            'imageBack',
            'isDefault'
        ])
        -> first();

        return $address ? $this->assembleImage($address) : $address ;
    }

    public function getSend($id)
    {
        $address = DB::table('erp_mp_shop_send_address')
        -> where('isDel', 0)
        -> where('id', $id)
        -> select([
            'id',
            'userId',
            'name',
            'phone',
            'province',
            'city',
            'area',
            'detail',
            'idNumber',
            'imageFront',
            'imageBack',
            'isDefault'
        ])
        -> first();

        return $address ? $this->assembleImage($address) : $address ;
    }

    public function fetchDefaultSend($businessId=49)
    {
        $default_address = (object)[
            'province' =>  '江苏省',
            'city'     =>  '苏州市',
            'area'     =>  '吴中区',
            'phone'    =>  '17195134748',
            'name'     =>  '李先生',
            'detail'   =>  '金丝港39号'
        ];

        $address = DB::table('erp_send_address')
        -> select([
            'province',
            'city',
            'area',
            'phone',
            'name',
            'address as detail'
        ])
        -> where([
            'business_id' => $businessId,
            'flag' => 0
        ]) -> first();

        return $address ? $address : $default_address;
    }

    public function checkExistAddressById($id, $userId)
    {
        if (empty($id)) {
            return false;
        }

        $count = DB::table('erp_mp_shop_address')
        -> where('userId', $userId)
        -> where('isDel', 0)
        -> where('id', $id)
        -> count();

        return $count > 0;
    }

    public function checkExistSendAddressById($id, $userId)
    {
        if (empty($id)) {
            return false;
        }

        $count = DB::table('erp_mp_shop_send_address')
            -> where('userId', $userId)
            -> where('isDel', 0)
            -> where('id', $id)
            -> count();

        return $count > 0;
    }

    /**
     * 地址查询
     */
    public function search($request)
    {
        $list = DB::table('erp_mp_shop_address')
        -> where('userId', $request->user->wxUserId)
        -> where('isDel', 0)
        -> select([
            "id",
            "userId",
            "name",
            "phone",
            "province",
            "city",
            "area",
            "detail",
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

    public function searchSend($request)
    {
        $list = DB::table('erp_mp_shop_send_address')
        -> where('userId', $request->user->wxUserId)
        -> where('isDel', 0)
        -> select([
            "id",
            "userId",
            "name",
            "phone",
            "province",
            "city",
            "area",
            "detail",
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
        $address = $this->get($id);
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

        DB::table('erp_mp_shop_address')
        -> where('id', $request->id)
        -> update([
            'name'          => $request->name ? $request->name : $address->name ,
            'phone'         => $request->phone ? $request->phone : $address->phone ,
            'province'      => $request->province ? $request->province : $address->province ,
            'city'          => $request->city ? $request->city : $address->city ,
            'area'          => $request->area ? $request->area : $address->area ,
            'detail'        => $request->detail ? $request->detail : $address->detail ,
            'idNumber'      => $request->idNumber ? $request->idNumber : $address->idNumber ,
            'imageFront'    => $request->imageFront ? $request->imageFront : $address->imageFront ,
            'imageBack'     => $request->imageBack ? $request->imageBack : $address->imageBack ,
            'updatedTime'   => time()
        ]);

        if (!empty($request->isDefault)) {
            $this->setDefault($request->user->wxUserId, $id);
        }

        return true;
    }

    public function editSend($request, $id)
    {
        $address = $this->getSend($id);
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

        DB::table('erp_mp_shop_send_address')
        -> where('id', $request->id)
        -> update([
            'name'          => $request->name ? $request->name : $address->name ,
            'phone'         => $request->phone ? $request->phone : $address->phone ,
            'province'      => $request->province ? $request->province : $address->province ,
            'city'          => $request->city ? $request->city : $address->city ,
            'area'          => $request->area ? $request->area : $address->area ,
            'detail'        => $request->detail ? $request->detail : $address->detail ,
            'idNumber'      => $request->idNumber ? $request->idNumber : $address->idNumber ,
            'imageFront'    => $request->imageFront ? $request->imageFront : $address->imageFront ,
            'imageBack'     => $request->imageBack ? $request->imageBack : $address->imageBack ,
            'updatedTime'   => time()
        ]);

        if (!empty($request->isDefault)) {
            $this->setSendDefault($request->user->wxUserId, $id);
        }

        return true;
    }

    private function assembleImage($address)
    {
        $address->backName = $address->imageBack;
        $address->frontName = $address->imageFront;
        $address->imageFront = $address->imageFront ? getImageUrl($address->imageFront) : '' ;
        $address->imageBack = $address->imageBack ? getImageUrl($address->imageBack) : '' ;
        return $address;
    }

    /**
     * 获取默认地址
     */
    public function getDefault($userId)
    {
        $address = DB::table('erp_mp_shop_address')
        -> where('userId', $userId)
        -> where('isDel', 0)
        -> where('isDefault', 1)
        -> select([
            "id",
            "userId",
            "name",
            "phone",
            "province",
            "city",
            "area",
            "detail",
            "idNumber",
            "imageFront",
            "imageBack",
        ])
        -> first();

        if ($address) {
            $address = $this->assembleImage($address);
            return $address;
        } else {
            return new \stdClass();
        }
    }

    public function getSendDefault($userId)
    {
        $address = DB::table('erp_mp_shop_send_address')
            -> where('userId', $userId)
            -> where('isDel', 0)
            -> where('isDefault', 1)
            -> select([
                "id",
                "userId",
                "name",
                "phone",
                "province",
                "city",
                "area",
                "detail",
                "idNumber",
                "imageFront",
                "imageBack",
            ])
            -> first();

        if ($address) {
            $address = $this->assembleImage($address);
            return $address;
        } else {
            return new \stdClass();
        }
    }

    /**
     * 删除地址
     */
    public function del($id)
    {
        DB::table('erp_mp_shop_address')
            ->where('id', $id)
            -> update([
                'isDel'         => 1,
                'updatedTime'   => time()
            ]);
        return true;
    }

    public function delSend($id)
    {
        DB::table('erp_mp_shop_send_address')
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
        DB::table('erp_mp_shop_address')
        -> where('id', $id)
        -> update([
            'isDefault'     => 1,
            'updatedTime'   => time()
        ]);

        // 其他所有都取消默认
        DB::table('erp_mp_shop_address')
        -> where('userId', $userId)
        -> where('id', '!=', $id)
        -> update([
            'isDefault'     => 0,
            'updatedTime'   => time()
        ]);

        return true;
    }

    public function setSendDefault($userId, $id)
    {
        // 设置这个为默认，
        DB::table('erp_mp_shop_send_address')
            -> where('id', $id)
            -> update([
                'isDefault'     => 1,
                'updatedTime'   => time()
            ]);

        // 其他所有都取消默认
        DB::table('erp_mp_shop_send_address')
            -> where('userId', $userId)
            -> where('id', '!=', $id)
            -> update([
                'isDefault'     => 0,
                'updatedTime'   => time()
            ]);

        return true;
    }

}
