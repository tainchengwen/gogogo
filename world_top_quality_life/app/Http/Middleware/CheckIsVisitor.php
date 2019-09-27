<?php

namespace App\Http\Middleware;

use Closure;

class CheckIsVisitor
{
    /**
     * 检测是不是游客
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->user->user_id){
            return response(['code'=>401,'msg'=>'游客用户请先授权'],401);
        }

        return $next($request);
    }
}
