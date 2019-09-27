<?php


namespace App\Http\Controllers\ApiWechatApplet\Traits;


trait ApiResponse
{
    /**
     * 成功返回
     * @param string $message
     * @param array $data
     * @param int $http
     * @param array $header
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($message='', $data=[], $http=200, $header=[])
    {
        $return = [
            'require_id' => 'S_' . uniqid(),
            'code' => 'ok',
            'message' => $message ?: '操作成功',
            'data' => $data ?: [],
        ];
        return response()->json($return, $http, $header);
    }

    /**
     * 失败返回
     * @param int $http
     * @param string $message
     * @param array $header
     * @return \Illuminate\Http\JsonResponse
     */
    public function error( $message='',$http=200, $header=[])
    {
        return response()->json([
            'require_id' => 'E_' . uniqid(),
            'code' => 'fail',
            'message' => $message ?: '操作失败'
        ], $http, $header);
    }
}
