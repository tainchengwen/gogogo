<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title></title>
    <style>
        .b-box{
            width:26%;
            height:180px;
            float:left;
            margin-left:5%;
            margin-top:20px;
            //border:1px solid #000000;
        }
        .s-box{
            width:100%;
            text-align:center;
            font-size:15px;
            height:30px;
            line-height:15px;
        }
        .s-img{
            width:100%;
            height:80px;
        }
    </style>
</head>
<body style="height:1160px;width:820px;">

<div style="padding:10px;width:100%;" >
    @foreach($packages as $key => $vo)
    <div class="b-box">

        <img class="s-img" src="http://{{ $url }}/barcodegen/example/code/test_code128.php?text={{ $vo -> package_num }}" />
        <div class="s-box">{{ $vo -> package_num }}</div>
        <div class="s-box">{{ $vo -> weight }} <a style="margin-left:10px;">{{ $vo -> name }}</a></div>
        <!--
        <img class="s-img" src="http://api.okayapi.com/?s=Ext.BarCode.Gen&check_sum={{ $vo -> package_num }}" />
        -->
    </div>


    @endforeach
</div>



</body>
</html>
