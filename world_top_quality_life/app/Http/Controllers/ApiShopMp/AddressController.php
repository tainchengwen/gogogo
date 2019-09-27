<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\AddressRepository;
use Illuminate\Http\Request;
use App\MallApi;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function __construct(AddressRepository $addressRepository)
    {
        $this->addressRepository = $addressRepository;
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|max:60',
            'phone'         => 'required|numeric',
            'place'         => 'required|array|min:3',
            'detail'        => 'required|max:255',
            'idNumber'      => 'max:18',
            'imageFront'    => 'max:120',
            'imageBack'     => 'max:120'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        if (empty($request->place[0]) || empty($request->place[1]) || empty($request->place[2])) {
            return $this->errorResponse($request, [], '缺少必要省市区参数');
        }

        if (in_array('全部', $request->place)) {
            return $this->errorResponse($request, [], '请选择正确的省市区哦~');
        }

        if (in_array($request->place[0],['台湾省', '香港特别行政区', '澳门特别行政区'])) {
            return $this->errorResponse($request, [], '暂不支持邮寄到'.$request->place[0]);
        }

        $request->province  = $request->place[0];
        $request->city      = $request->place[1];
        $request->area      = $request->place[2];

        $this->addressRepository->add($request);

        return $this->successResponse($request, []);
    }

    public function del(Request $request, $id)
    {
        $address = $this->addressRepository->get($id);
        if (empty($address)) {
            return $this->successResponse($request, []);
        }

        $this->addressRepository->del($id);
        return $this->successResponse($request, []);
    }

    public function list(Request $request)
    {

        $list = $this->addressRepository->search($request);

        return $this->successResponse($request, $list);
    }

    public function edit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'max:60',
            'phone'         => 'numeric',
            'place'         => 'array|min:3',
            'detail'        => 'max:255',
            'idNumber'      => 'max:18',
            'imageFront'    => 'max:120',
            'imageBack'     => 'max:120'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        if (!empty($request->place)) {
            if (empty($request->place[0]) || empty($request->place[1]) || empty($request->place[2])) {
                return $this->errorResponse($request, [], '缺少必要省市区参数');
            }

            if (in_array('全部', $request->place)) {
                return $this->errorResponse($request, [], '请选择正确的省市区哦~');
            }

            if (in_array($request->place[0],['台湾省', '香港特别行政区', '澳门特别行政区'])) {
                return $this->errorResponse($request, [], '暂不支持邮寄到'.$request->place[0]);
            }
            $request->province  = $request->place[0];
            $request->city      = $request->place[1];
            $request->area      = $request->place[2];
        }

        $this->addressRepository->edit($request, $id);

        return $this->successResponse($request, []);
    }

    public function smart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $address = $request->address;
        $address = preg_replace('# #','',$address);
        $model = new MallApi();
        $res = $model -> getAddressBySF($address);
        return $this->successResponse($request, json_decode($res)->obj);
    }

    //发货地址
    public function createSend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|max:60',
            'phone'         => 'required|numeric',
            'place'         => 'required|array|min:3',
            'detail'        => 'required|max:255',
            'idNumber'      => 'max:18',
            'imageFront'    => 'max:120',
            'imageBack'     => 'max:120'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        if (empty($request->place[0]) || empty($request->place[1]) || empty($request->place[2])) {
            return $this->errorResponse($request, [], '缺少必要省市区参数');
        }

        if (in_array('全部', $request->place)) {
            return $this->errorResponse($request, [], '请选择正确的省市区哦~');
        }

        if (in_array($request->place[0],['台湾省', '香港特别行政区', '澳门特别行政区'])) {
            return $this->errorResponse($request, [], '暂不支持从'.$request->place[0].'发货');
        }

        $request->province  = $request->place[0];
        $request->city      = $request->place[1];
        $request->area      = $request->place[2];

        $this->addressRepository->addSend($request);

        return $this->successResponse($request, []);
    }

    public function delSend(Request $request, $id)
    {
        $address = $this->addressRepository->getSend($id);
        if (empty($address)) {
            return $this->successResponse($request, []);
        }

        $this->addressRepository->delSend($id);
        return $this->successResponse($request, []);
    }

    public function listSend(Request $request)
    {

        $list = $this->addressRepository->searchSend($request);

        return $this->successResponse($request, $list);
    }

    public function editSend(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'max:60',
            'phone'         => 'numeric',
            'place'         => 'array|min:3',
            'detail'        => 'max:255',
            'idNumber'      => 'max:18',
            'imageFront'    => 'max:120',
            'imageBack'     => 'max:120'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }
        if (!empty($request->place)) {
            if (empty($request->place[0]) || empty($request->place[1]) || empty($request->place[2])) {
                return $this->errorResponse($request, [], '缺少必要省市区参数');
            }

            if (in_array('全部', $request->place)) {
                return $this->errorResponse($request, [], '请选择正确的省市区哦~');
            }

            if (in_array($request->place[0],['台湾省', '香港特别行政区', '澳门特别行政区'])) {
                return $this->errorResponse($request, [], '暂不支持从'.$request->place[0].'发货');
            }
            $request->province  = $request->place[0];
            $request->city      = $request->place[1];
            $request->area      = $request->place[2];
        }

        $this->addressRepository->editSend($request, $id);

        return $this->successResponse($request, []);
    }

}