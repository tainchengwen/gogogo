<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class MpRender
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
            ]);
        }

        return $next($request);
    }
}
