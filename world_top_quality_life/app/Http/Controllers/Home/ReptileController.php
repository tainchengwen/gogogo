<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReptileController extends Controller
{
    //
    public function test(){
        $code = $_GET['code'];
        $url = 'https://shopping.yahoo.co.jp/search?first=1&p='.$code;

        /*
        $url = 'https://shopping.yahoo.co.jp/search?first=1&p=4908049429690';
        $contents = file_get_contents($url);
        file_put_contents('/usr/local/var/www/htdocs/package/storage/logs/newtest.txt',$contents);exit;
*/


        //$contents = file_get_contents('/usr/local/var/www/htdocs/package/storage/logs/newtest.txt');
        $contents = file_get_contents($url);
        $pattern = "/<div class=\"uiContentsList\">([\s\S]*?)<\/div>/iUs";

        preg_match_all($pattern, $contents, $output);


        $contents2 = $output[0][0];

        $pattern_2 = "/<li([\s\S]*?)<\/li>/is";

        preg_match_all($pattern_2, $contents2, $output2);

        $temp = [];
        foreach($output2[0] as $k => $value){
            //拿图片 拿价格 拿商品名称

            //取图片
            $pattern_img = "/<img src([\s\S]*?)>\n/is";
            preg_match_all($pattern_img, $value, $image);

            //取名称
            $pattern_name = "/<span>([\s\S]*?)<\/span>/is";
            preg_match_all($pattern_name, $value, $name);

            /*
                0 => array:5 [▼
                        0 => "<span>ディープチャージ コラーゲン ドリンク 約10日分 【ファンケル 公式】</span>"
                        1 => "<span>2,468</span>"
                        2 => "<span>ポイント1倍</span>"
                        3 => "<span>通常ポイント</span>"
                        4 => "<span>（+1倍）</span>"
                      ]
            */
            if(isset($image[0][0]) && isset($name[0][0])){
                $temp[$k]['image'] = $image[0][0];

                $temp[$k]['name'] = isset($name[0][0])?$name[0][0]:'';
                $temp[$k]['price'] = isset($name[0][1])?$name[0][1]:'';
                $temp[$k]['temp1'] = isset($name[0][2])?$name[0][2]:'';
                $temp[$k]['temp2'] = isset($name[0][3])?$name[0][3]:'';
                $temp[$k]['temp3'] = isset($name[0][4])?$name[0][4]:'';
            }


        }

        dd($temp);



    }
}
