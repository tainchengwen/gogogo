<?php

namespace Spatie\Permission\Middlewares;

use App\Log;
use Closure;
use Spatie\Permission\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    public function handle($request, Closure $next, $permission)
    {
        //return $next($request);
        if (app('auth')->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        /*
         * $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);
         *
         */
        $user = app('auth')->user();
        if($user -> username == 'admin'){
            return $next($request);
        }

        $permissions = $user->getAllPermissions();
        if(!count($permissions)){
            throw UnauthorizedException::forPermissions($permissions);
        }

        foreach($permissions as $vo){
            if($vo -> id == $permission){
                return $next($request);
            }
        }


        throw UnauthorizedException::forPermissions($permissions);



        $f_permissions_ids = [];
        foreach($permissions as $k => $vo){
            if($vo -> fid){
                $f_permissions_ids[]= $vo -> id;
            }
        };


        $user_has_permissions = array_values(array_unique($f_permissions_ids));
        \Illuminate\Support\Facades\Log::info(json_encode($user_has_permissions));
        //return $user_has_permissions;
        if(in_array($permission,$user_has_permissions)){
            return $next($request);
        }


        throw UnauthorizedException::forPermissions($permissions);
    }
}
