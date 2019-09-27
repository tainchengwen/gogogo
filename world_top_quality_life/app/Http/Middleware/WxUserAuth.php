<?php
namespace App\Http\Middleware;

use App\WxUsers as ShopMPUsers;
use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

// 注意，我们要继承的是 jwt 的 BaseMiddleware
class WxUserAuth extends BaseMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     * @throws JWTException
     */
    public function handle($request, Closure $next)
    {   
        // 检查此次请求中是否带有 token，如果没有则抛出异常。
        $authToken = Auth::guard('wx')->getToken();

        if(!$authToken){
            return response(['code'=>401,'msg'=>'Token not provided'],401);
        }

        // 检测用户的登录状态，如果正常则通过
        if (Auth::guard('wx')->check()) {
            $WXUserId = Auth::guard('wx')->payload()['sub'];
            $time = Auth::guard('wx')->payload()['exp'];

            $request->user = ShopMPUsers::where([
                'mp_shop_users.id'    => $WXUserId
            ])
            -> leftJoin('wxuser','mp_shop_users.user_id','wxuser.id')
            -> select([
                'mp_shop_users.*',
                'wxuser.market_class',
                'wxuser.id as wxUserId',
                'wxuser.price',
                'wxuser.fandian',
                'wxuser.is_new',
                'wxuser.last_popup_at',
            ])
            -> first();

            //如果是游客,补全游客信息
            if (!$request->user->user_id){
                $visitor=config('admin.visitor');
                $request->user->nickname=$visitor['nickname'];
                $request->user->headimg=$visitor['headimg'];
                $request->user->market_class=$visitor['market_class'];
                $request->user->wxUserId=0;
                $request->user->price=$visitor['price'];
                $request->user->fandian=$visitor['fandian'];
                $request->user->is_new=$visitor['is_new'];
            }

            //刷新Token
            if(($time - time()) < 10*600 && ($time - time()) > 0){
                $token = Auth::guard('wx')->refresh();
                if($token){
                    // $request->headers->set('Authorization', 'Bearer '.$token);
                    $request->refreshToken = $token;
                }else{
					return response(['code'=>401,'msg'=>'The token has been blacklisted'],401);
                }

                // 在响应头中返回新的 token
                $respone = $next($request);
                if(isset($token) && $token){
                    // $respone->headers->set('Authorization', 'Bearer '.$token);
                    $request->refreshToken = $token;
                }
                return $respone;
            }

            //token通过验证 执行下一补操作
            return $next($request);
        }
		return response(['code'=>401,'msg'=>'The token has been blacklisted'],401);
    }
}
