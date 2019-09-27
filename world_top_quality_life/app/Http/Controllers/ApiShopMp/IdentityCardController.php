<?php

namespace App\Http\Controllers\ApiShopMp;

use App\Repositories\IdentityCardRepository;
use Illuminate\Http\Request;
use App\MallApi;
use App\Http\Controllers\ApiShopMp\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IdentityCardController extends Controller
{
    public function __construct(IdentityCardRepository $identityCardRepository)
    {
        $this->identityCardRepository = $identityCardRepository;
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|max:60',
            'idNumber'      => 'required|max:18',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少或必要参数错误');
        }

        $request->imageFront=$request->filled('imageFront') ? 'ali_oss:'.$request->imageFront : '';
        $request->imageBack=$request->filled('imageBack') ? 'ali_oss:'.$request->imageBack : '';

        $this->identityCardRepository->add($request);

        return $this->successResponse($request, []);
    }

    public function del(Request $request, $id)
    {
        $address = $this->identityCardRepository->get($id);
        if (empty($address)) {
            return $this->successResponse($request, []);
        }

        $this->identityCardRepository->del($id);
        return $this->successResponse($request, []);
    }

    public function list(Request $request)
    {
        $list = $this->identityCardRepository->search($request);

        return $this->successResponse($request, $list);
    }

    public function edit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'max:60',
            'idNumber'      => 'max:18',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($request, [], '缺少必要参数');
        }

        $request->imageFront=$request->filled('imageFront') ? 'ali_oss:'.$request->imageFront : '';
        $request->imageBack=$request->filled('imageBack') ? 'ali_oss:'.$request->imageBack : '';

        $this->identityCardRepository->edit($request, $id);

        return $this->successResponse($request, []);
    }

}
