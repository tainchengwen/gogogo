<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MpPageController extends Controller
{
    //新手教程
    public function page1(){
        return view('home.mp_page.page1');
    }

    //产品服务
    public function page2(){
        return view('home.mp_page.page2');
    }

    //运费时效
    public function page3(){
        return view('home.mp_page.page3');
    }

    //禁用物品
    public function page4(){
        return view('home.mp_page.page4');
    }

    //商品违禁品规则
    public function page5(){
        return view('home.mp_page.page5');
    }

    //照片存档规则
    public function page6(){
        return view('home.mp_page.page6');
    }




}
