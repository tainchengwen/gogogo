<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <style>
        body,html {
            margin: 0;
            padding-left:3px;
        }
        .content {
            height:600px;
            width:350px;
            margin: 0 auto;
            background-color: #ffffff;
            border:1px solid #000000;
            padding-left:3px;
        }
        .top{
            padding-top:5px;
            width:100%;
            height:40px;
            line-height:40px;
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            position:relative;
        }
        .top-img{
            display:none;
            width:40px;
            height:40px;
            position:absolute;
            left:10px;
        }


        .scan{
            height:110px;
            width:100%;
        }
        .scan-top{
            height:30px;
            line-height:30px;
            text-align: center;
        }
        .scan-num{
            height:50px;
            width:100%;
        }
        .s-img{
            height:100%;
            width: 94%;
            margin-left:3%;
        }
        .scan-bottom{
            height:30px;
            line-height:30px;
            font-size:20px;
            font-weight:bold;
            text-align: center;
        }
        .cang_num{
            height:40px;
            line-height:40px;
            font-size:25px;
            font-weight: bolder;
            width:100%;
            text-align: center;
            border-top:1px solid #000000;
        }
        .danhao{
            height:42px;
            line-height:42px;
            font-size:20px;
            width:100%;
            text-align: left;
        }
        .column{
            height:42px;
            line-height:42px;
            font-size:20px;
            width:100%;
            text-align: left;
            position:relative;
            border-top:1px solid #000000;
        }
        .text_check{
            position:absolute;
            width:30px;
            height:30px;
            border:1px solid #000000;
            bottom:5px;
            right:10px;
        }

    </style>
</head>
<body>

<div class="content">

    <div class="top">
        <img src="{{ asset('img/logo.png') }}" class="top-img" />
        展鵬國際供應鏈有限公司
    </div>
    <div class="scan">
        <div class="scan-top">
            識別碼
        </div>
        <div class="scan-num">
            <img class="s-img" src="http://{{ env('PDF_URL') }}/barcodegen/example/code/test_code128.php?text={{ config('admin.repertory_id_prefix').sprintf('%06s',$_GET['s_number']) }}"  />
        </div>
        <div class="scan-bottom">
            {{ config('admin.repertory_id_prefix').sprintf('%06s',$_GET['s_number']) }}
        </div>
    </div>

    <div class="cang_num">
        倉位：{{ $_GET['cangwei'] }}
    </div>


    <div class="danhao">
        物流/送貨單號：{{ $_GET['danhao'] }}
    </div>


    @if($arr)
        @foreach($arr as $vo)
            <div class="column">
                {{ $vo }}
                <div class="text_check"></div>
            </div>
            @endforeach
    <div class="column">
        備註：做完記得打√
    </div>
        @endif


</div>


</body>
</html>
