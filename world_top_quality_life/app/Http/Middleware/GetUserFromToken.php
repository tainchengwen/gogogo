<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class GetUserFromToken
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

        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'errcode' => 400004,
                    'errmsg' => 'user not found'
                ]);
            }
            # 后台用户信息直接放入request
            $request->user = $user;

        } catch (TokenExpiredException $e) {

            return response()->json([
                'errcode' => 400001,
                'errmsg' => 'token expired'
            ]);

        } catch (TokenInvalidException $e) {

            return response()->json([
                'errcode' => 400003,
                'errmsg' => 'token invalid'
            ]);

        } catch (JWTException $e) {

            return response()->json([
                'errcode' => 400002,
                'errmsg' => 'token absent'
            ]);

        }

        //记录操作日志

        $user = auth('api')->user();
        DB::table('erp_operation_log') -> insertGetId([
            'user_id' => $user -> id,
            'method' => $_SERVER['REQUEST_METHOD'],
            'ip' => $_SERVER['SERVER_ADDR'],
            'input' => json_encode($request->all()),
            'path' => $_SERVER['REQUEST_URI'],
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        return $next($request);
    }
}
