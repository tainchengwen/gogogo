<?php

namespace App\Http\Controllers\Home;

use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FriendController extends Controller
{
    //
    public function index(){
        $userid = session('home_user_id');
        $config = env('APP_ENV');
        if($config != 'local'){
            if(!$userid){

                //回去回调用
                $config = [
                    'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                    'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
                    // ...
                    'oauth' => [
                        'scopes'   => ['snsapi_userinfo'],
                        'callback' => '/redirect',
                    ],
                    // ..
                ];

                $app = Factory::officialAccount($config);
                $oauth = $app->oauth;
                session([
                    'target_url' => url('myfriend')
                ]);
                return $oauth->redirect(url('redirect'));
            }

            $config = [
                'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
                'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            ];

            $app = Factory::officialAccount($config);
            $qrcode_key = $userid;
            if($userid == 41){
                $qrcode_key = 'auto_c_class';
                Log::info('key_info:'.$qrcode_key);
            }
            $result = $app->qrcode->temporary($qrcode_key, 6 * 24 * 3600);

            $ticket = $result['ticket'];
            $url = $app->qrcode->url($ticket);
        }else{
            $userid = 41;
            $url = 'http://thirdwx.qlogo.cn/mmopen/Q3auHgzwzM6cFWcibEomYpLUloZwwfGNxY8WAnzibgMdXoqfd2e5pyLqdMtsJN8tLzaGH1LObYLmnuQiazE8UgGRg/132';
        }

        //var_dump($url);exit;

        $users = DB::table('wxuser') -> where([
            'from_userid' => $userid
        ]) -> get();


        //查看is_look是0的数量
        $count_look = DB::table('order') -> where([
            'userid' => $userid,
            'is_look' => 0
        ]) -> count();


        return view('home.myfriend') -> with([
            'users' => $users,
            'url' => $url,
            'count_look' => $count_look
        ]);
    }
}
