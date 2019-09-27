<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class ShopMpRender
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $input = $request->all();
        if(!isset($input['photo'])){
            DB::table('mp_operation_log') -> insertGetId([
                'method' => $_SERVER['REQUEST_METHOD'],
                'ip' => $_SERVER['REMOTE_ADDR'],
                'input' => json_encode($request->all()),
                'path' => $_SERVER['REQUEST_URI'],
                'created_at' => time(),
                'updated_at' => time(),
                'wx_user_id' => isset($request->user->wxUserId)?$request->user->wxUserId:0,
            ]);
        }

        if (!empty($request->business_id)) {
            $self = DB::table('erp_business')
            -> where('id', $request->business_id)
            -> first();
            if (!$self) {
                $self = DB::table('erp_business')
                -> where('self_status', '1')
                -> first();
            }
        } else {
            $self = DB::table('erp_business')
            -> where('self_status', '1')
            -> first();
        }

        // 当前事业部
        $request->business = $self;
        // 是否是自营
        $request->isSelf = $self->self_status === 1;
        return $next($request);
    }
}
